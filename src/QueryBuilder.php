<?php

namespace GeovaniRangel\ModelLayer;

use PDO;
use PDOException;

trait QueryBuilder
{
    public function getString(): string
    {
        return trim($this->query);
    }

    public function find(string $cols = "*"): self
    {
        if ($cols != "*" and strpos($cols, $this->primaryKey) === false) {
            $cols = "{$this->entityName}.{$this->primaryKey}, " . trim($cols);
        }

        $this->query = " SELECT {$cols} FROM {$this->entityName}";

        return $this;
    }

    public function innerJoin(Entity $entity): self
    {
        $this->query .= " INNER JOIN {$entity->getName()}";
        $this->foreignEntity = $entity;

        return $this;
    }

    public function leftJoin(Entity $entity): self
    {
        $this->query .= " LEFT JOIN {$entity->getName()}";
        $this->foreignEntity = $entity;

        return $this;
    }

    public function rightJoin(Entity $entity): self
    {
        $this->query .= " RIGHT JOIN {$entity->getName()}";
        $this->foreignEntity = $entity;

        return $this;
    }

    public function on(string $fkRefer): self
    {
        if ($this->foreignEntity !== null) {
            if (in_array($fkRefer, $this->foreignEntity->getCols())){
                $this->query .= " ON {$this->entityName}.{$this->primaryKey} = {$this->foreignEntity->getName()}.{$fkRefer}";
                return $this;
            }
            else {
                throw new MLException("Foreign key({$fkRefer}) does not belong to entity({$this->foreignEntity->getName()}).");
            }
        } else {
            throw new MLException("You can only use the ON clause when it is followed by a JOIN.");
        }
    }

    public function where(string $conditions, ?string $parameters = null): self
    {
        $this->query .= " WHERE {$conditions}";

        if ($parameters !== null) {
            parse_str($parameters, $this->parameters);
        }

        return $this;
    }

    public function group(string $group): self
    {
        $this->query .= " GROUP BY {$group}";

        return $this;
    }

    public function order(string $order): self
    {
        $this->query .= " ORDER BY {$order}";

        return $this;
    }

    public function limit(string $limit): self
    {
        $this->query .= " LIMIT {$limit}";

        return $this;
    }

    public function offset(string $offset): self
    {
        $this->query .= " OFFSET {$offset}";

        return $this;
    }

    public function fetch(bool $all = true)
    {
        try {
            $handler = Connection::open("select");

            $this->statement = $handler->prepare(trim($this->query));
            $this->statement->execute($this->parameters);

            if ($all) {
                $this->data = $this->statement->fetchAll();

                $this->getedNewData();

                return $this;
            } else {
                $this->data = $this->statement->fetchObject();

                $this->getedNewData();

                return $this;
            }
        } catch (PDOException $e) {
            $this->error = $e;
            return $this;
        }
    }

    public function fetchGet(bool $all = true)
    {
        try {
            $handler = Connection::open("select");

            $this->statement = $handler->prepare(trim($this->query));
            $this->statement->execute($this->parameters);

            if ($all) {
                return $this->statement->fetchAll();

            } else {
                return $this->statement->fetchObject();
            }
        } catch (PDOException $e) {
            $this->error = $e;
            return null;
        }
    }

    public function query(string $query, ?string $parameters = null): self
    {
        $this->query = $query;
        if ($parameters !== null) {
            parse_str($parameters, $this->parameters);
        }

        return $this;
    }
}
