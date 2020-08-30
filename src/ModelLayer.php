<?php

namespace GeovaniRangel\ModelLayer;

use GeovaniRangel\ModelLayer\Utils\Connection;

/**
 * @link Documentação https://github.com/geovanirangel/modellayer/blob/master/README.md
 * @author Geovani Rangel <dev.geovanirangel@gmail.com>
 * @license MIT
 * 
 * @version v3alpha
 */
abstract class ModelLayer
{
    const STRING = "string";
    const INTEGER = "integer";
    const FLOAT = "float";
    const BOOLEAN = "boolean";

    const DATETIME_FORMAT = "Y-m-d H:i:s";

    /** @var \Throwable $error last/current error */
    protected $error = null;

    /** @var string $query */
    protected $query = null;

    /** @var array $parameters */
    protected $parameters = null;

    /** @var \PDOStatement $statement */
    protected $statement = null;

    public function __debugInfo()
    {
        return $this->debug();
    }

    public function debug(): array
    {
        return [
            "query" => $this->getQuery(),
            "parameters" => $this->parameters,
            "error" => $this->error
        ];
    }

    public function setQuery(string $query)
    {
        $this->query = $query;
        return $this;
    }

    public function setParameters(string $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function getQuery(): string
    {
        return str_replace(array_keys($this->parameters), $this->parameters, $this->query);
    }

    public function getError(): \Throwable
    {
        return $this->error;
    }

    public function getSQLState(): int
    {
        return $this->error->errorInfo[0];
    }

    public function run(string $operation = Operation::ROOT, string $query, ?array $parameters = null)
    {
        $this->parameters = $parameters;
        $this->query = $query;
        try {
            $connection = Connection::get($operation);
            $this->statement = $connection->prepare($this->query);

            $success = $this->statement->execute($this->parameters);

            if ($operation == Operation::READ){
                return $this->statement;
            }
            elseif ($operation == Operation::WRITE){
                return (int)$connection->lastInsertId();
            }
            else {
                return $success;
            }
        }
        catch (\Throwable $th)
        {
            $this->error = $th;
            return false;
        }
    }
}
