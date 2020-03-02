<?php

namespace GeovaniRangel\ModelLayer;

use DateTime;
use PDO;
use PDOException;
use stdClass;

/**
 * Abstração de um CRUD
 * 
 * @link Documentação https://github.com/geovanirangel/modellayer/blob/master/README.md
 * @author Geovani Rangel <dev.geovanirangel@gmail.com>
 * @license MIT
 * 
 * @version 1.0
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
     * @param string $entity_name Nome da entidade.
     * @param null|string $conditions Condições para usar na cláusula WHERE SQL.
     * @param null|string $group GROUP BY Statement SQL.
     * @param null|string $order ORDER BY Keyword SQL.
     * @param null|string $limit valor usado na cláusula LIMIT SQL.
     * @param null|string $offset valor usado na cláusula OFFSET.
     * @param null|string $parameters String de paramêtros/valores usados na execução do Statement como valores das cláusulas WHERE por exemplo. Formato "index=value".
     * @param bool $close Se true a conexão aberta será fechada, caso contrátio permanece aberta (recomenda-se usar true).
     * @return bool|array|stdClass
     **/
    protected function select(bool $all = true, string $cols = "*", string $entity_name, ?string $conditions = null, ?string $group = null, ?string $order = null, ?string $limit = null, ?string $offset = null, ?string $parameters = null, bool $close = true)
    {
        $handler = Connection::open("select");

        $query = "SELECT {$cols} FROM {$entity_name}";

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
                unset($handler);
            }

            if ($all) {
                return $this->statement->fetchAll(PDO::FETCH_CLASS);
            } else {
                return $this->statement->fetchObject();
            }
        } catch (PDOException $e) {
            $this->error = $e;
            return false;
        }
    }


    /**
     * Abstração para DELETE Statement SQL
     * @param string $entity_name Nome da entidade.
     * @param null|string $conditions Condições para usar na cláusula WHERE SQL.
     * @param null|string $parameters String de paramêtros/valores usados na execução do Statement como valores das cláusulas WHERE por exemplo. Formato "index=value".
     * @param bool $close Se true a conexão aberta será fechada, caso contrátio permanece aberta (recomenda-se usar true).
     * @return boolean True no caso de sucesso, false em falha.
     **/
    protected function delete(string $entity_name, string $conditions, string $parameters, bool $close = true): bool
    {
        $handler = Connection::open("delete");
        $sql = "DELETE FROM {$entity_name} WHERE {$conditions}";
        parse_str($parameters, $this->parameters);

        try {
            $this->statement = $handler->prepare($sql);

            if ($close) {
                $handler = null;
                unset($handler);
            }

            return $this->statement->execute($this->parameters);
        } catch (PDOException $e) {
            $this->error = $e;
            return false;
        }
    }


    /**
     * Abstração para UPDATE Statement SQL
     *
     * @param string $entity_name Nome da entidade.
     * @param array $entity_data Array com colunas e valores. Espera-se também um índice "null" informado se o campo pode ficar nullo.
     * @param null|string $conditions Condições para usar na cláusula WHERE SQL.
     * @param null|string $parameters String de paramêtros/valores usados na execução do Statement como valores das cláusulas WHERE por exemplo. Formato "index=value". Os valores passados em $entity_data serão convertidos em parâmetros para serem usados  na execução automaticamente.
     * @param bool $close Se true a conexão aberta será fechada, caso contrário permanece aberta (recomenda-se usar true).
     * @return bool True no caso de sucesso, false em falha.
     **/
    protected function update(string $entity_name, array $entity_data, ?string $conditions = null, ?string $parameters = null, string $date_format = "Y-m-d H:i:s", bool $close = true): bool
    {
        $values = $parameters;
        foreach ($entity_data as $col => $e) {
            if (is_numeric($col)) {
                throw new MLException("Enter an associative array with names, not numbers.");
            } else {
                $null = $entity_data[$col]["null"] ?? false;
                $value = $entity_data[$col]["value"] ?? null;
                $updated = $entity_data[$col]["updated"] ?? false;

                if ($updated) {
                    $value = (new DateTime("now"))->format($date_format);
                }

                if (($value == null or $value == "") and $null === false) {
                    throw new MLException("The \"{$col}\" column cannot be empty.");
                }

                $temp[$col] = "{$col} = :col_{$col}";
                $values .= "&:col_{$col}={$value}";
            }
        }
        $fields = implode(", ", $temp);
        unset($temp);

        $sql = "UPDATE {$entity_name} SET {$fields}";
        if ($conditions !== null) {
            $sql .= " WHERE {$conditions}";
        }

        parse_str($values, $this->parameters);

        $handler = Connection::open("update");

        try {
            $this->statement = $handler->prepare($sql);

            if ($close) {
                $handler = null;
                unset($handler);
            }

            return $this->statement->execute($this->parameters);
        } catch (PDOException $e) {
            $this->error = $e;
            return false;
        }
    }


    /**
     * Abstração para INSERT INTO Statement SQL
     *
     * @param string $entity_name Nome da entidade.
     * @param array $entity_data Array com colunas e valores. Espera-se também um índice "null" informado se o campo pode ficar nullo.
     * @param bool $close Se true a conexão aberta será fechada, caso contrátio permanece aberta (recomenda-se usar true).
     * @return bool True no caso de sucesso, false em falha.
     **/
    protected function insert(string $entity_name, array $entity_data, string $date_format = "Y-m-d H:i:s", bool $close = true): bool
    {
        $values = "";
        foreach ($entity_data as $col => $e) {
            if (is_numeric($col)) {
                throw new MLException("Enter an associative array with names, not numbers.");
            } else {
                $null = $entity_data[$col]["null"] ?? false;
                $value = $entity_data[$col]["value"] ?? null;
                $created = $entity_data[$col]["created"] ?? false;
                $updated = $entity_data[$col]["updated"] ?? false;

                if ($created or $updated) {
                    $value = (new DateTime("now"))->format($date_format);
                }

                if (($value == null or $value == "") and $null === false) {
                    throw new MLException("The \"{$col}\" column cannot be empty.");
                }

                $temp["fields"][$col] = $col;
                $temp["fields_paramenters"][$col] = ":col_{$col}";
                $values .= "&col_{$col}={$value}";
            }
        }
        $fields = implode(", ", $temp["fields"]);
        $fields_paramenters = implode(", ", $temp["fields_paramenters"]);
        unset($temp);

        parse_str($values, $this->parameters);

        $sql = "INSERT INTO {$entity_name} ({$fields}) VALUES ({$fields_paramenters})";

        $handler = Connection::open("insert");

        try {
            $this->statement = $handler->prepare($sql);

            if ($close) {
                $handler = null;
                unset($handler);
            }

            return $this->statement->execute($this->parameters);
        } catch (PDOException $e) {
            $this->error = $e;
            return false;
        }
    }
}
