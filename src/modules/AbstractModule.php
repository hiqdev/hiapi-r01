<?php

namespace hiapi\r01\modules;

use hiapi\r01\R01Tool;

class AbstractModule
{
    protected $tool;
    protected $base;

    /**
     * AbstractModule constructor.
     * @param R01Tool $tool
     */
    public function __construct(R01Tool $tool)
    {
        $this->tool = $tool;
        $this->base = $tool->getBase();
    }

    /**
     * @param string $name
     * @return string
     */
    protected function fiorus (string $name): string
    {
        return preg_replace('/[^\-a-zA-Z\xC0-\xFF\xA3\xB3 ]/u' , ' ', $name);
    }

    /**
     * @param string $name
     * @return string
     */
    protected function fioeng (string $name): string
    {
        return preg_replace('/[^\-a-zA-Z_ ]/', ' ', $name);
    }

    /**
     * @param string $id
     * @param bool $org
     * @return null|string
     */
    protected function nic_hdl(?string $id, bool $org=false): ?string
    {
        if (preg_match('/^[A-Z0-9_]+-(R01|ORG)$/', $id)) {
            return $id;
        }
        if ($id) {
            $id = preg_replace('/[^A-Z0-9_]/', '_' , strtoupper($id));
            $id .= ($org ? '-ORG' : '') . '-GPT';
        }
        return $id;
    }
}
