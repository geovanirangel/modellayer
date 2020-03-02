<?php

namespace GeovaniRangel\ModelLayer;

use Exception;

class MLException extends Exception
{
    public function __construct(string $msg, $code = 0)
    {
        parent::__construct($msg, $code);
    }

    public function __toString()
    {
        return parent::getMessage();
    }
}
