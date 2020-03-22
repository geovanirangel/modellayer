<?php

namespace Models;

use GeovaniRangel\ModelLayer\Entity;

class Adress extends Entity
{
    public function __construct()
    {
        parent::__construct(
            "adresses",
            "id",
            array(
                "adress" => ["null" => false],
                "user_id" => ["null" => false, "foreignEntity" => "Models\User"]
            )
        );
    }
}