<?php

namespace GeovaniRangel\ModelLayer;

abstract class Model extends ModelLayer
{
    use Traits\CrudTrait, Traits\QueryTrait;

    const OPTIONS_DEFAULT = [
        "empty" => false,
        "created" => false,
        "updated" => false,
        "type" => ModelLayer::STRING
    ];

    /** @var array $columns */
    private $columns;

    /** @var bool|array|\stdClass $data data from queries  */
    private $data = null;

    /** @var string $entityName */
    private $entityName;

    /** @var string $primaryKey */
    private $primaryKey;

    /** @var bool $strictMode If active, single queries will be typed according to the settings */
    private $strictMode = false;


    public function __construct(string $entityName, string $primaryKey, array $columns)
    {
        $this->entityName = $entityName;
        $this->primaryKey = $primaryKey;
        $this->columns = $columns;
        $this->columns[$primaryKey] = [
            "type" => ModelLayer::INTEGER
        ];
    }
    
    public function __get($name)
    {
        if (in_array($name, $this->getColumnNames())) {
            settype($this->data->$name, $this->getColumnDataType($name));
            return $this->data->$name;
        }
        return $this->$name;
    }

    public function __set($name, $value)
    {
        if (in_array($name, $this->getColumnNames())) {
            if (empty($this->data)){
                $this->data = new \stdClass;
            }

            settype($value, $this->getColumnDataType($name));
            $this->data->$name = $value;
            return;
        }
        $this->$name = $value;
        return;
    }

    public function __serialize(): array
    {
        return $this->data;
    }

    public function __unserialize(array $data): void
    {
        $this->data = $data;
        return;
    }

    public function find(string $cols = "*"): self
    {
        return $this->find($cols)->from($this->entityName);
    }

    public function getByPk(int $primaryKeyValue, string $cols = "*"): self
    {
        $this->data = $this->select($cols, $this->getEntityName(), $this->getPKName()." = :p1", [":p1" => $primaryKeyValue], null, null, null, false);
        $this->typeIfStrictModeOn();
        return $this;
    }

    public function getBy(string $columnName, $columnValue, string $cols = "*"): self
    {
        $this->data = $this->select($cols, $this->getEntityName(), "{$columnName} = :param_{$columnName}", [":param_{$columnName}" => $columnValue], null, null, null, false);
        $this->typeIfStrictModeOn();
        return $this;
    }

    public function getAll(string $cols = "*"): self
    {
        $this->data = $this->select($cols, $this->getEntityName(), null, null, null, null, null, true);
        return $this;
    }
    
    public function getAllBy(string $conditions, array $parameters = null, string $cols = "*"): self
    {
        $this->data = $this->select($cols, $this->getEntityName(), $conditions, $parameters, null, null, null, true);
        return $this;
    }

    public function delByPk(int $primaryKeyValue): bool
    {
        return $this->delete($this->getEntityName(), "id = :id", [":id" => $primaryKeyValue]);
    }

    public function delAllBy(string $conditions, array $parameters): bool
    {
        return $this->delete($this->getEntityName(), $conditions, $parameters);
    }

    public function save()
    {
        if (is_array($this->data) OR empty($this->data) OR !is_object($this->data)){
            $this->error = new Utils\ModelLayerException("Could not be saved. Data must be stdClass object.");
            return false;
        }

        $this->checkEmptyFields();
        
        if (property_exists($this->data, $this->getPKName())){
            $data = $this->dataForUpdate();
            unset($data[$this->getPKName()]);
            return $this->update($this->getEntityName(), $data, $this->getPKName()." = :param1", [":param1" => $this->getPkValue()]);
        }
        else {
            $data = $this->dataForInsert();
            return $this->insert($this->getEntityName(), $data);
        }
    }


    // Model Helpers

    public function addValue(string $name, $value)
    {
        $this->data->$name = $value;
    }

    public function checkEmptyFields()
    {
        foreach ($this->data as $columnName => $value){
            if (empty($value) AND !($this->columns[$columnName]["empty"] ?? self::OPTIONS_DEFAULT["empty"])){
                throw new Utils\ModelLayerException("The \"{$columnName}\" field cannot be empty");
            }
        }
        return;
    }

    public function data(bool $typed = false)
    {
        if ($typed){
            $this->typed();
        }
        return $this->data;
    }

    public function dataIsEmpty(): bool
    {
        return empty($this->data);
    }

    public function debug(bool $exit = true): array
    {
        $r = [
            "data" => $this->data
        ]+parent::debug();
        if ($exit){
            return $r;
            exit;
        }
        return $r;
    }

    public function getColumnNames(): array
    {
        return array_keys($this->columns);
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumnDataType(string $columnName): string
    {
        return $this->columns[$columnName]["type"] ?? self::STRING;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getPKName(): string
    {
        return $this->primaryKey;
    }

    public function getPKValue(bool $typed = true): int
    {
        $pkName = $this->getPKName();
        if ($typed){
            settype($this->data->$pkName, $this->getColumnDataType($pkName));
        }
        return $this->data->$pkName;
    }
    
    public function json($options = 0): string
    {
        return json_encode($this->data, $options);
    }

    public function setData($data): self
    {
        $this->data = $data;
        return $this;
    }

    public function strictMode(?bool $newValue = null): bool
    {
        if (empty($newValue)){
            return $this->strictMode;
        }
        return $this->strictMode = $newValue;
    }

    public function typed(): void
    {
        if (!is_array($this->data)){
            foreach ($this->data as $columnName => $columnValue){
                settype($columnValue, $this->getColumnDataType($columnName));
                $newData[$columnName] = $columnValue;
            }
    
            $this->data = (object)$newData;
        }
        return;
    }


    // private helpers

    private function typeIfStrictModeOn(): void
    {
        if ($this->strictMode){
            $this->typed();
        }
        return;
    }

    private function dataForInsert(): array
    {
        $newData = (array)$this->data;
        foreach ($this->columns as $columnName => $columnOptions) {
            if ($columnOptions["created"] ?? false){
                $newData[$columnName] = date(ModelLayer::DATETIME_FORMAT);
            }

            if ($columnOptions["updated"] ?? false){
                $newData[$columnName] = date(ModelLayer::DATETIME_FORMAT);
            }
        }

        return $newData;
    }

    private function dataForUpdate(): array
    {
        $newData = (array)$this->data;
        foreach ($this->columns as $columnName => $columnOptions) {
            if ($columnOptions["updated"] ?? false){
                $newData[$columnName] = date(ModelLayer::DATETIME_FORMAT);
            }
        }

        return $newData;
    }
}
