<?php

namespace Models;

use GeovaniRangel\ModelLayer\Model;
use GeovaniRangel\ModelLayer\ModelLayer;

class User extends Model
{
    public function __construct()
    {
        parent::__construct(
            "users",
            "id",
            [
                "name" => [],
                "email" => ["empty" => true],
                "code" => ["type" => ModelLayer::INTEGER],
                "created_at" => ["created" => true],
                "updated_at" => ["updated" => true]
            ]
        );
    }
}
