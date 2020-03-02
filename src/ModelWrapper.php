<?php

namespace GeovaniRangel\ModelLayer;

use stdClass;

trait ModelWrapper
{
    public function getAll(string $cols = "*", ?string $limit = null, ?string $offset = null): self
    {
        if ($cols != "*" and strpos($cols, $this->primary_key) === false) {
            $cols = "{$this->primary_key}, " . $cols;
        }
        $this->data = $this->select(true, $cols, $this->entity_name, null, null, null, $limit, $offset);
        return $this;
    }

    public function getByPK(string $val, string $cols = "*"): self
    {
        if ($cols != "*" and strpos($cols, $this->primary_key) === false) {
            $cols = "{$this->primary_key}, " . $cols;
        }
        $this->data = $this->select(false, $cols, $this->entity_name, "{$this->primary_key} = :{$this->primary_key}", null, null, null, null, ":{$this->primary_key}={$val}");
        return $this;
    }

    public function getBy(string $col, string $val, string $cols = "*"): self
    {
        if (!in_array($col, array_keys($this->cols))) {
            throw new MLException("{$col} column was not mapped in the constructor method.");
        }

        if ($cols != "*" and strpos($cols, $this->primary_key) === false) {
            $cols = "{$this->primary_key}, " . $cols;
        }
        $this->data = $this->select(false, $cols, $this->entity_name, "{$col} = :{$col}", null, null, null, null, ":{$col}={$val}");
        return $this;
    }

    public function delByPK(?string $value = null): bool
    {
        if ($value === null) {
            $pk = $this->primary_key;
            if ($this->data instanceof stdClass and isset($this->data->$pk)) {
                $value = $this->data->$pk;

                if ($this->delete($this->entity_name, "{$this->primary_key} = :{$this->primary_key}", ":{$this->primary_key}={$value}")) {
                    $this->data = null;
                    return true;
                } else {
                    return false;
                }
            } else {
                throw new MLException("No data found. Before deleting, get one via getByPK(), for example, or enter a value for the primary key manually.");
            }
        } else {
            if ($this->delete($this->entity_name, "{$this->primary_key} = :{$this->primary_key}", ":{$this->primary_key}={$value}")) {
                $this->data = null;
                return true;
            } else {
                return false;
            }
        }
    }

    public function del(string $conditions, string $parameters): bool
    {
        if ($this->delete($this->entity_name, $conditions, $parameters)) {
            $this->data = null;
            return true;
        } else {
            return false;
        }
    }


    public function save(): bool
    {
        if ($this->error() === null) {
            $data = (array) $this->data;
            $pk = $this->primary_key;

            if ($this->data instanceof stdCLass and isset($this->data->$pk)) {
                foreach ($this->cols as $col => $opt) {
                    if ($col != $this->primary_key) {
                        $entity_data[$col] = $opt;
                        $entity_data[$col]["value"] = trim(($data[$col] ?? ""));
                    }
                }
                return $this->update($this->entity_name, $entity_data, "{$this->primary_key} = :pk", ":pk={$this->data->$pk}");
            } elseif ($this->data instanceof stdCLass and count($data) > 0) {
                foreach ($this->cols as $col => $opt) {
                    if ($col != $this->primary_key) {
                        $entity_data[$col] = $opt;
                        $entity_data[$col]["value"] = trim(($data[$col] ?? ""));
                    }
                }
                return $this->insert($this->entity_name, $entity_data);
            } else {
                if (is_array($this->data)) {
                    throw new MLException("Multiple results found. Get just one to save.");
                } else {
                    throw new MLException("Couldn't save. No data was found.");
                }
            }
        } else {
            throw new MLException("Unable to save because an error was encountered.");
        }
    }
}
