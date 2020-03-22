<?php

namespace Models;

use GeovaniRangel\ModelLayer\Entity;

class User extends Entity
{
    public function __construct()
    {
        parent::__construct(
            "users",
            "id",
            [
                "name" => [],
                "email" => ["null" => false],
                "created" => ["created" => true],
                "updated" => ["updated" => true]
            ]
        );
    }
}
