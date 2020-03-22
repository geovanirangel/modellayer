<?php

namespace GeovaniRangel\ModelLayer;

use stdClass;

trait ModelWrapper
{
    public function getAll(string $cols = "*", ?string $limit = null, ?string $offset = null): self
    {
        if ($cols != "*" and strpos($cols, $this->primaryKey) === false) {
            $cols = "{$this->primaryKey}, " . $cols;
        }
        $this->data = $this->select(true, $cols, $this->entityName, null, null, null, $limit, $offset);

        $this->getedNewData();

        return $this;
    }

    public function getByPK(string $val, string $cols = "*"): self
    {
        if ($cols != "*" and strpos($cols, $this->primaryKey) === false) {
            $cols = "{$this->primaryKey}, " . $cols;
        }
        $this->data = $this->select(false, $cols, $this->entityName, "{$this->primaryKey} = :{$this->primaryKey}", null, null, null, null, ":{$this->primaryKey}={$val}");

        $this->getedNewData();

        return $this;
    }

    public function getBy(string $col, string $val, string $cols = "*"): self
    {
        if (!in_array($col, array_keys($this->cols))) {
            throw new MLException("{$col} column was not mapped in the constructor method.");
        }

        if ($cols != "*" and strpos($cols, $this->primaryKey) === false) {
            $cols = "{$this->primaryKey}, " . $cols;
        }
        $this->data = $this->select(false, $cols, $this->entityName, "{$col} = :{$col}", null, null, null, null, ":{$col}={$val}");

        $this->getedNewData();

        return $this;
    }

    public function delByPK(?string $value = null): bool
    {
        if ($value === null) {
            $pk = $this->primaryKey;
            if ($this->data instanceof stdClass and isset($this->data->$pk)) {
                $value = $this->data->$pk;

                if ($this->delete($this->entityName, "{$this->primaryKey} = :{$this->primaryKey}", ":{$this->primaryKey}={$value}")) {
                    $this->data = null;
                    return true;
                } else {
                    return false;
                }
            } else {
                throw new MLException("No data found. Before deleting, get one via getByPK(), for example, or enter a value for the primary key manually.");
            }
        } else {
            if ($this->delete($this->entityName, "{$this->primaryKey} = :{$this->primaryKey}", ":{$this->primaryKey}={$value}")) {
                $this->data = null;
                return true;
            } else {
                return false;
            }
        }
    }

    public function del(string $conditions, string $parameters): bool
    {
        if ($this->delete($this->entityName, $conditions, $parameters)) {
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
            $pk = $this->primaryKey;

            if ($this->data instanceof stdCLass and isset($this->data->$pk)) {
                foreach ($this->cols as $col => $opt) {
                    if ($col != $this->primaryKey) {
                        $entityData[$col] = $opt;
                        $entityData[$col]["value"] = trim(($data[$col] ?? ""));
                    }
                }
                return $this->update($this->entityName, $entityData, "{$this->primaryKey} = :pk", ":pk={$this->data->$pk}");
            } elseif ($this->data instanceof stdCLass and count($data) > 0) {
                foreach ($this->cols as $col => $opt) {
                    if ($col != $this->primaryKey) {
                        $entityData[$col] = $opt;
                        $entityData[$col]["value"] = trim(($data[$col] ?? ""));
                    }
                }
                return $this->insert($this->entityName, $entityData);
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
