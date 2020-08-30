<?php

namespace GeovaniRangel\ModelLayer\Utils;

use GeovaniRangel\ModelLayer\Operation;

final class Connection
{
    /** @var \PDO $connection */
    private static $connection = null;

    /** @var string $lastOperationType */
    private static $lastOperationType = null;

    public static function get($operation = Operation::ROOT, $config = MODEL_LAYER_CONFIG): \PDO
    {
        if (empty(self::$connection) OR $operation != self::$lastOperationType){
            $username = $config["users"][$operation]["name"] ?? $config["users"][Operation::ROOT]["name"] ?? "";
            $password = $config["users"][$operation]["password"] ?? $config["users"][Operation::ROOT]["password"] ?? "";

            $driver = $config["driver"];
            $host = $config["host"];
            $port = $config["port"];
            $dbname = $config["dbname"];
            $charset = $config["charset"];

            self::$connection = new \PDO("{$driver}:host={$host}:{$port};dbname={$dbname};charset={$charset}", $username, $password, $config["options"]);
        }

        self::$lastOperationType = $operation;
        return self::$connection;
    }

    private function __construct(){}

    private function __clone(){}

    private function __wakeup(){}
}
