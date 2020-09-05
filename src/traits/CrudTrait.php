<?php

namespace Traits;

trait CrudTrait
{
    public function select(string $columns = "*", string $entityName, ?string $conditions = null, ?array $parameters = null, ?string $order = null, ?string $limit = null, ?string $offset = null, bool $all = true)
    {
        $sql = "SELECT {$columns} FROM {$entityName}";

        if (!empty($conditions)){
            $sql .= " WHERE {$conditions}";
        }
        if (!empty($order)){
            $sql .= " ORDER BY {$order}";
        }
        if (!empty($limit)){
            $sql .= " LIMIR {$limit}";
        }
        if (!empty($offset)){
            $sql .= " OFFSET {$offset}";
        }

        $this->parameters = $parameters;
        $this->query = $sql;

        $this->statement = $this->run(__FUNCTION__, $this->query, $this->parameters);
        if ($all){
            return $this->statement->fetchAll();
        }
        return $this->statement->fetchObject();
    }

    public function delete(string $entityName, string $conditions, ?array $parameters = null): bool
    {
        $sql = "DELETE FROM {$entityName}";
        if (!empty($conditions)){
            $sql .= " WHERE {$conditions}";
        }

        $this->parameters = $parameters;
        $this->query = $sql;

        return $this->run(__FUNCTION__, $this->query, $this->parameters);
    }

    public function insert(string $entityName, array $data)
    {
        foreach ($data as $key => $value) {
            $columns[] = $key;
            $parameters[":".$key] = $value;
        }

        $sql = "INSERT INTO {$entityName}";
        $sql .= " (" . implode(", ", $columns) . ")";
        $sql .= " VALUES (" . implode(", ", array_keys($parameters)) . ")";

        $this->query = $sql;
        $this->parameters = $parameters;

        return $this->run(__FUNCTION__, $this->query, $this->parameters);
    }

    public function update(string $entityName, array $data, string $conditions, ?array $parameters = null): bool
    {
        foreach ($data as $key => $value){
            $columns[] = $key. " = " . ":col_".$key;
            $parameters[":col_".$key] = $value;
        }

        $sql = "UPDATE {$entityName} SET";
        $sql .= " " . implode(", ", $columns);

        if (!empty($conditions)){
            $sql .= " WHERE {$conditions}";
        }

        $this->query = $sql;
        $this->parameters = $parameters;

        return $this->run(__FUNCTION__, $this->query, $this->parameters);
    }
}
