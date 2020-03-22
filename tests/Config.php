<?php

// Exemplo de conexão
define("DBCONFIG", [
    "driver" => "mysql",
    "host" => "localhost",
    "port" => "3306",
    "charset" => "utf8mb4",
    "dbname" => "database_name",
    "users" => [
        "default" => [
            "name" => "username",
            "password" => "password"
        ]
    ],
    "options" => [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_CASE => PDO::CASE_NATURAL
    ]
]);