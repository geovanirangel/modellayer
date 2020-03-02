<?php

namespace Models;

use GeovaniRangel\ModelLayer\Entity;

class User extends Entity
{
    public function __construct()
    {
        parent::__construct(
            "user",
            "id",
            [
                "name" => ["null" => false],
                "email" => ["null" => false],
                "created" => ["created" => true],
                "updated" => ["updated" => true]
            ]
        );
    }
}
