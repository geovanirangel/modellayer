<?php

namespace GeovaniRangel\ModelLayer;

use Exception;
use PDOStatement;
use PDOException;
use stdClass;
use Throwable;

/**
 * Abstração entidades de bancos de dados SQL com PDO
 * 
 * @link Documentação https://github.com/geovanirangel/modellayer/blob/master/README.md
 * @author Geovani Rangel <dev.geovanirangel@gmail.com>
 * @license MIT
 * 
 * @version 1.0
 */
abstract class Entity
{
    use CrudTrait;
    use QueryBuilder;
    use ModelWrapper;

    /** @var array $cols */
    private $cols;

    /** @var string $primary_key */
    private $primary_key;

    /** @var string $entity_name */
    private $entity_name;

    /** @var array|stdClass $data*/
    private $data;

    /** @var string $query */
    private $query;

    /** @var array $parameters */
    private $parameters;

    /** @var Entity $foreign_entity */
    private $foreign_entity;

    /** @var PDOStatement $statement */
    private $statement;

    /** @var PDOException|Exception $error */
    private $error;

    public function __construct(string $name, string $primary_key, array $cols)
    {
        if (!(count($cols) > 0)) {
            throw new MLException("Provide at least one column.");
        }

        $this->entity_name = $name;
        $this->primary_key = $primary_key;

        foreach ($cols as $col_name => $e) {
            if (is_numeric($col_name)) {
                throw new MLException("Enter an associative array with names, not numbers.");
            }

            $this->cols[$col_name]["created"] = filter_var($cols[$col_name]["created"] ?? false, FILTER_VALIDATE_BOOLEAN);

            $this->cols[$col_name]["updated"] = filter_var($cols[$col_name]["updated"] ?? false, FILTER_VALIDATE_BOOLEAN);

            $this->cols[$col_name]["null"] = filter_var($cols[$col_name]["null"] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $this->cols[$primary_key] = ["null" => false, "updated" => false, "created" => false];
    }

    public function __set($name, $value)
    {
        if (in_array($name, array_keys($this->cols))) {
            if ($this->data === false or $this->data === null and !is_array($this->data) and !($this->data instanceof stdClass)) {
                $this->data = new stdClass();
            }

            $this->data->$name = (string) $value;
        } else {
            $this->$name = $value;
        }
    }

    public function __get($name)
    {
        if (in_array($name, array_keys($this->cols))) {
            return $this->data->$name ?? null;
        } else {
            return $this->$name ?? null;
        }
    }

    public function __isset($name)
    {
        if (in_array($name, array_keys($this->cols))) {
            return isset($this->data->$name);
        } else {
            return isset($this->$name);
        }
    }

    public function __unset($name)
    {
        if (in_array($name, array_keys($this->cols))) {
            unset($this->data->$name);
        } else {
            unset($this->$name);
        }
    }

    public function error(): ?Throwable
    {
        return ($this->error !== null and $this->error instanceof Throwable) ? $this->error : null;
    }

    public function sqlState(): ?int
    {
        if ($this->error !== null and $this->error instanceof PDOException) {
            return $this->error->errorInfo[0] ?? null;
        } else {
            return null;
        }
    }

    public function data()
    {
        if ($this->data instanceof stdClass or is_array($this->data)) {
            return $this->data;
        } else {
            return null;
        }
    }

    public function exist(): bool
    {
        if (!self::isEmpty($this->data) and $this->data instanceof stdClass) {
            return true;
        } elseif (is_array($this->data)) {
            throw new MLException("You cannot use the exist() or found() method with arrays.");
        } else {
            return false;
        }
    }

    public function found()
    {
        if ($this->exist()) {
            return $this;
        } else {
            return false;
        }
    }

    public function count(): int
    {
        return $this->affectedRows();
    }

    public function getName(): string
    {
        return $this->entity_name;
    }

    public function getPKName(): string
    {
        return $this->primary_key;
    }

    public function getCols(): array
    {
        return $this->cols;
    }

    private function isEmpty($input): bool
    {
        if ($input === null or $input === "") {
            return true;
        } else {
            return false;
        }
    }

    private function affectedRows()
    {
        if ($this->statement !== null and $this->statement instanceof PDOStatement) {
            try {
                return $this->statement->rowCount();
            } catch (PDOException $e) {
                $this->error = $e;
                return false;
            }
        } else {
            throw new MLException("PDOStatement not found.");
        }
    }
}
