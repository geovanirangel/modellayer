<?php

namespace GeovaniRangel\ModelLayer\Traits;

trait QueryTrait
{
    public function find(string $cols = "*"): self
    {
        $this->query .= "SELECT {$cols}";
        return $this;
    }

    public function from(string $entityName): self
    {
        $this->query .= " FROM {$entityName}";
        return $this;
    }

    public function where(string $conditions, ?array $parameters = null): self
    {
        $this->query .= " WHERE {$conditions}";
        $this->parameters = $parameters;
        return $this;
    }

    public function order(string $column, string $type = "asc"): self
    {
        $this->query .= " ORDER BY {$column} {$type}";
        return $this;
    }

    public function orderBy(string $order): self
    {
        $this->query .= " ORDER BY {$order}";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->query .= " LIMIT {$limit}";
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->query .= " OFFSET {$offset}";
        return $this;
    }

    public function fetch(bool $all = true)
    {
        $this->statement = $this->run("select", $this->query, $this->parameters);

        if ($all) {
            return $this->statement->fetchAll();
        } else {
            return $this->statement->fetchObject();
        }
    }
}
