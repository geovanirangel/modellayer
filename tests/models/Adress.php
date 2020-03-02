<?php

namespace Models;

use GeovaniRangel\ModelLayer\Entity;

class Adress extends Entity
{
    public function __construct()
    {
        parent::__construct(
            "adress",
            "id",
            array(
                "adress" => ["null" => false],
                "user_id" => ["null" => false]
            )
        );
    }
}