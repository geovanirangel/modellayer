<?php

namespace Utils;

final class ModelLayerException extends \RuntimeException
{
    public function __toString()
    {
        return parent::getMessage();
    }
}
