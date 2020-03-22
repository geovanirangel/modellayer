<?php

namespace GeovaniRangel\ModelLayer;

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
 * @version 2.1.0
 */
abstract class Entity
{
    use CrudTrait;
    use QueryBuilder;
    use ModelWrapper;

    /** @var array $cols Estrutura da entidade */
    private $cols;

    /** @var string $primaryKey Nome da chave primária */
    private $primaryKey;

    /** @var string $entityName Nome da entidade */
    private $entityName;

    /** @var array|stdClass $data Dados obtidos em consultas */
    private $data;

    /** @var string $query*/
    private $query;

    /** @var array $parameters */
    private $parameters;

    /** @var Entity $foreignEntity */
    private $foreignEntity;

    /** @var PDOStatement $statement */
    private $statement;

    /** @var Throwable $error Última exceção/erro ocorrida(o) */
    private $error;

    private function getForeignEntitys(): void
    {
        if ($this->data() !== null) {
            foreach ($this->cols as $col => $e) {
                if ($e["foreignEntity"] !== null){
                    $p = $e["propertyName"];
                    $this->$p = (new $e["foreignEntity"])->find()->where($e["fkRefer"]." = :fkr", ":fkr={$this->data->$col}")->fetch($e["hasMany"]);
                    if ($e["hasMany"]){
                        $this->$p = $this->$p->data();
                    }
                    unset($p);
                }
            }
        }

        return;
    }

    public function __construct(string $name, string $primaryKey, array $cols)
    {
        if (!(count($cols) > 0)) {
            throw new MLException("Provide at least one column.");
        }

        $this->entityName = $name;
        $this->primaryKey = $primaryKey;

        foreach ($cols as $colName => $e) {
            if (is_numeric($colName)) {
                throw new MLException("Enter an associative array with names, not numbers.");
            }

            $this->cols[$colName]["null"] = filter_var($cols[$colName]["null"] ?? false, FILTER_VALIDATE_BOOLEAN);

            $this->cols[$colName]["created"] = filter_var($cols[$colName]["created"] ?? false, FILTER_VALIDATE_BOOLEAN);

            $this->cols[$colName]["updated"] = filter_var($cols[$colName]["updated"] ?? false, FILTER_VALIDATE_BOOLEAN);

            $this->cols[$colName]["foreignEntity"] = $cols[$colName]["foreignEntity"] ?? null;

            if ($this->cols[$colName]["foreignEntity"] !== null AND class_exists($this->cols[$colName]["foreignEntity"])){
                $this->cols[$colName]["hasMany"] = filter_var($cols[$colName]["hasMany"] ?? false, FILTER_VALIDATE_BOOLEAN);
                $this->cols[$colName]["fkRefer"] = $cols[$colName]["fkRefer"] ?? null;

                if (!isset($cols[$colName]["propertyName"])){
                    $this->cols[$colName]["propertyName"] = explode("\\", $this->cols[$colName]["foreignEntity"]);
                    $this->cols[$colName]["propertyName"] = filter_var(strtolower(array_pop($this->cols[$colName]["propertyName"])),FILTER_SANITIZE_STRING);
                }
                else {
                    $this->cols[$colName]["propertyName"] = filter_var($cols[$colName]["propertyName"],FILTER_SANITIZE_STRING);
                }

                if (!isset($cols[$colName]["fkRefer"])){
                    $this->cols[$colName]["fkRefer"] = (new $this->cols[$colName]["foreignEntity"]())->getPKName();
                }
                else {
                    $this->cols[$colName]["fkRefer"] = $cols[$colName]["fkRefer"];
                }
            }
            else {
                $this->cols[$colName]["hasMany"] = false;
                $this->cols[$colName]["fkRefer"] = null;
                $this->cols[$colName]["propertyName"] = null;
            }
            
        }

        $this->cols[$primaryKey] = [
            "null" => false,
            "updated" => false,
            "created" => false,
            "foreignEntity" => null,
            "hasMany" => false,
            "fkRefer" => null,
            "propertyName" => null
        ];
    }

    public function __set($name, $value)
    {
        if (in_array($name, array_keys($this->cols))) {
            if ($this->data === false OR $this->data === null AND !is_array($this->data) AND !($this->data instanceof stdClass)) {
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
        return ($this->error !== null AND $this->error instanceof Throwable) ? $this->error : null;
    }

    public function sqlState(): ?int
    {
        if ($this->error !== null AND $this->error instanceof PDOException) {
            return $this->error->errorInfo[0] ?? null;
        } else {
            return null;
        }
    }

    public function data()
    {
        if (($this->data instanceof stdClass OR is_array($this->data)) AND count((array)$this->data) > 0) {
            return $this->data;
        } else {
            return null;
        }
    }

    public function exist(): bool
    {
        if (!self::isEmpty($this->data) AND $this->data instanceof stdClass) {
            return true;
        } elseif (is_array($this->data)) {
            throw new MLException("You cannot use the exist() OR found() method with arrays.");
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

    public function count()
    {
        if ($this->statement !== null AND $this->statement instanceof PDOStatement) {
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

    public function getName(): string
    {
        return $this->entityName;
    }

    public function getPKName(): string
    {
        return $this->primaryKey;
    }

    public function getCols(): array
    {
        return $this->cols;
    }

    public function isEmpty($input): bool
    {
        if ($input === null OR $input === "") {
            return true;
        } else {
            return false;
        }
    }

    public function getedNewData(): void
    {
        $this->getForeignEntitys();
        return;
    }

    public function addData(string $name, $data): void
    {
        $this->data->$name = $data;
        return;
    }
}
