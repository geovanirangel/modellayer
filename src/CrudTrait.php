<?php

namespace GeovaniRangel\ModelLayer;

/**
 * Abstração de um CRUD
 * 
 * @link Documentação https://github.com/geovanirangel/modellayer/blob/master/README.md
 * @author Geovani Rangel <dev.geovanirangel@gmail.com>
 * @license MIT
 * 
 * @version 2.2.0
 */
trait CrudTrait
{
    /**
     * Abstração para SELECT Statement SQL
     *
     * Com os parâmetros informados gera uma query sql, executa e retorna os dados obtidos
     *
     * @param bool $all Informe true para fetchAll e false para fetchObject.
     * @param string $cols Colunas a serem trazidas das consultas em notação SQL (cuidados com os aliases). Ex: "name, email" ou "*" como padrão
     * @param string $entityName Nome da entidade.
     * @param null|string $conditions Condições para usar na cláusula WHERE SQL.
     * @param null|string $group GROUP BY Statement SQL.
     * @param null|string $order ORDER BY Keyword SQL.
     * @param null|string $limit valor usado na cláusula LIMIT SQL.
     * @param null|string $offset valor usado na cláusula OFFSET.
     * @param null|string $parameters String de paramêtros/valores usados na execução do Statement como valores das cláusulas WHERE por exemplo. Formato "index=value".
     * @param bool $close Se true a conexão aberta será fechada, caso contrátio permanece aberta (recomenda-se usar true).
     * @return bool|array|\stdClass
     **/
    protected function select(bool $all = true, string $cols = "*", string $entityName, ?string $conditions = null, ?string $group = null, ?string $order = null, ?string $limit = null, ?string $offset = null, ?string $parameters = null, bool $close = true)
    {
        $handler = Connection::open("select");

        $query = "SELECT {$cols} FROM {$entityName}";

        if ($conditions !== null) {
            $query .= " WHERE " . trim($conditions);
        }
        if ($group !== null) {
            $query .= " GROUP BY " . trim($group);
        }
        if ($order !== null) {
            $query .= " ORDER BY " . trim($order);
        }
        if ($limit !== null) {
            $query .= " LIMIT " . trim($limit);
        }
        if ($offset !== null) {
            $query .= " OFFSET " . trim($offset);
        }

        parse_str($parameters, $this->parameters);

        try {
            $this->statement = $handler->prepare($query);
            $this->statement->execute($this->parameters);

            if ($close) {
                $handler = null;
            }

            if ($all) {
                return $this->statement->fetchAll(\PDO::FETCH_CLASS);
            } else {
                return $this->statement->fetchObject();
            }
        } catch (\Throwable $th) {
            $this->error = $th;
            return false;
        }
    }


    /**
     * Abstração para DELETE Statement SQL
     * @param string $entityName Nome da entidade.
     * @param null|string $conditions Condições para usar na cláusula WHERE SQL.
     * @param null|string $parameters String de paramêtros/valores usados na execução do Statement como valores das cláusulas WHERE por exemplo. Formato "index=value".
     * @param bool $close Se true a conexão aberta será fechada, caso contrátio permanece aberta (recomenda-se usar true).
     * @return boolean True no caso de sucesso, false em falha.
     **/
    protected function delete(string $entityName, string $conditions, string $parameters, bool $close = true): bool
    {
        $handler = Connection::open("delete");
        $sql = "DELETE FROM {$entityName} WHERE {$conditions}";
        parse_str($parameters, $this->parameters);

        try {
            $this->statement = $handler->prepare($sql);

            if ($close) {
                $handler = null;
            }

            return $this->statement->execute($this->parameters);
        } catch (\Throwable $th) {
            $this->error = $th;
            return false;
        }
    }


    /**
     * Abstração para UPDATE Statement SQL
     *
     * @param string $entityName Nome da entidade.
     * @param array $entityData Array com colunas e valores. Espera-se também um índice "null" informado se o campo pode ficar nullo.
     * @param null|string $conditions Condições para usar na cláusula WHERE SQL.
     * @param null|string $parameters String de paramêtros/valores usados na execução do Statement como valores das cláusulas WHERE por exemplo. Formato "index=value". Os valores passados em $entityData serão convertidos em parâmetros para serem usados  na execução automaticamente.
     * @param bool $close Se true a conexão aberta será fechada, caso contrário permanece aberta (recomenda-se usar true).
     * @return bool True no caso de sucesso, false em falha.
     **/
    protected function update(string $entityName, array $entityData, ?string $conditions = null, ?string $parameters = null, string $dateFormat = "Y-m-d H:i:s", bool $close = true): bool
    {
        $values = $parameters;
        foreach ($entityData as $col => $e) {
            if (is_numeric($col)) {
                throw new MLException("Enter an associative array with names, not numbers.");
            } else {
                $null = $entityData[$col]["null"] ?? false;
                $value = $entityData[$col]["value"] ?? null;
                $updated = $entityData[$col]["updated"] ?? false;

                if ($updated) {
                    $value = (new \DateTime("now"))->format($dateFormat);
                }

                if (($value == null or $value == "") and $null === false) {
                    throw new MLException("The \"{$col}\" column cannot be empty.");
                }

                $temp[$col] = "{$col} = :col_{$col}";
                $values .= "&:col_{$col}={$value}";
            }
        }
        $fields = implode(", ", $temp);

        $sql = "UPDATE {$entityName} SET {$fields}";
        if ($conditions !== null) {
            $sql .= " WHERE {$conditions}";
        }

        parse_str($values, $this->parameters);

        $handler = Connection::open("update");

        try {
            $this->statement = $handler->prepare($sql);

            if ($close) {
                $handler = null;
            }

            return $this->statement->execute($this->parameters);
        } catch (\Throwable $th) {
            $this->error = $th;
            return false;
        }
    }


    /**
     * Abstração para INSERT INTO Statement SQL
     *
     * @param string $entityName Nome da entidade.
     * @param array $entityData Array com colunas e valores. Espera-se também um índice "null" informado se o campo pode ficar nullo.
     * @param bool $close Se true a conexão aberta será fechada, caso contrátio permanece aberta (recomenda-se usar true).
     * @return bool True no caso de sucesso, false em falha.
     **/
    protected function insert(string $entityName, array $entityData, string $dateFormat = "Y-m-d H:i:s", bool $close = true): bool
    {
        $values = "";
        foreach ($entityData as $col => $e) {
            if (is_numeric($col)) {
                throw new MLException("Enter an associative array with names, not numbers.");
            } else {
                $null = $entityData[$col]["null"] ?? false;
                $value = $entityData[$col]["value"] ?? null;
                $created = $entityData[$col]["created"] ?? false;
                $updated = $entityData[$col]["updated"] ?? false;

                if ($created or $updated) {
                    $value = (new \DateTime("now"))->format($dateFormat);
                }

                if (($value == null or $value == "") and $null === false) {
                    throw new MLException("The \"{$col}\" column cannot be empty.");
                }

                $temp["fields"][$col] = $col;
                $temp["fields_paramenters"][$col] = ":col_{$col}";
                $values .= "&:col_{$col}={$value}";
            }
        }
        $fields = implode(", ", $temp["fields"]);
        $fieldsParamenters = implode(", ", $temp["fields_paramenters"]);

        parse_str($values, $this->parameters);

        $sql = "INSERT INTO {$entityName} ({$fields}) VALUES ({$fieldsParamenters})";

        $handler = Connection::open("insert");

        try {
            $this->statement = $handler->prepare($sql);

            if ($close) {
                $handler = null;
            }

            return $this->statement->execute($this->parameters);
        } catch (\Throwable $th) {
            $this->error = $th;
            return false;
        }
    }
}
