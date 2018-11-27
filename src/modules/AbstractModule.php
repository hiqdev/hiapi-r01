<?php

namespace hiapi\r01\modules;

use hiapi\r01\R01Tool;

class AbstractModule
{
    protected $tool;

    public function __construct(R01Tool $tool)
    {
        $this->tool = $tool;
    }
}
