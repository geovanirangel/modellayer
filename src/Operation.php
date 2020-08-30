<?php

namespace GeovaniRangel\ModelLayer;

abstract class Operation
{
    const ROOT = "root";
    const READ = "select";
    const WRITE = "insert";
    const UPDATE = "update";
    const DELETE = "delete";
}
