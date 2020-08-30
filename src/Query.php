<?php

namespace GeovaniRangel\ModelLayer;

class Query extends ModelLayer
{
    use Traits\QueryTrait;
    
    public function __construct(?string $query = null, ?array $parameters = null) {
        if (!empty($query)){
            $this->query = $query;
        }

        if (!empty($parameters)){
            $this->parameters = $parameters;
        }
    }
}
