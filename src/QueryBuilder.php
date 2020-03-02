<?php

namespace GeovaniRangel\ModelLayer;

use PDOException;

trait QueryBuilder
{
    public function getString(): string
    {
        return trim($this->query);
    }

    public function find(string $cols = "*"): self
    {
        if ($cols != "*" and strpos($cols, $this->primary_key) === false) {
            $cols = "{$this->entity_name}.{$this->primary_key}, " . trim($cols);
        }

        $this->query = " SELECT {$cols} FROM {$this->entity_name}";

        return $this;
    }

    public function innerJoin(Entity $entity): self
    {
        $this->query .= " INNER JOIN {$entity->getName()}";
        $this->foreign_entity = $entity;

        return $this;
    }

    public function leftJoin(Entity $entity): self
    {
        $this->query .= " LEFT JOIN {$entity->getName()}";
        $this->foreign_entity = $entity;

        return $this;
    }

    public function rightJoin(Entity $entity): self
    {
        $this->query .= " RIGHT JOIN {$entity->getName()}";
        $this->foreign_entity = $entity;

        return $this;
    }

    public function on(string $fk_refer): self
    {
        if ($this->foreign_entity !== null) {
            $this->query .= " ON {$this->entity_name}.{$this->primary_key} = {$this->foreign_entity->getName()}.{$fk_refer}";
            return $this;
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
                return $this;
            } else {
                $this->data = $this->statement->fetchObject();
                return $this;
            }
        } catch (PDOException $e) {
            $this->error = $e;
            return $this;
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
