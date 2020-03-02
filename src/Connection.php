<?php

namespace GeovaniRangel\ModelLayer;

use PDO;
use PDOException;

abstract class Connection
{
    /**
     * Gera uma string dsn
     *
     * Gera uma string dsn para a construção do objeto PDO a partir de uma array de configurações
     *
     * @param array $config
     * @return string
     **/
    private static function dsn($config = DBCONFIG): string
    {
        $driver = $config["driver"];
        $host = $config["host"];
        $port = $config["port"];
        $dbname = $config["dbname"];
        $charset = $config["charset"];

        return "{$driver}:host={$host}:{$port};dbname={$dbname};charset={$charset}";
    }

    /**
     * Abre um conexão o banco de dados
     *
     * Abre um conexão o banco de dados a partir de uma array de configurações e de um usuário
     *
     * @param string $user
     * @param array $config
     * @return null|PDO
     * @throws PDOException caso a conexão falhe
     **/
    public static function open($user = "default", $config = DBCONFIG): ?PDO
    {
        $username = $config["users"][$user]["name"] ?? $config["users"]["default"]["name"] ?? null;
        $password = $config["users"][$user]["password"] ?? $config["users"]["default"]["password"] ?? null;

        $options = $config["options"];

        return new PDO(self::dsn(), $username, $password, $options);
    }

    public function __construct()
    {
    }

    public function __clone()
    {
    }

    public function __wakeup()
    {
    }
}
