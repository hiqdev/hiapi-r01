#!/usr/bin/env php
<?php

echo __DIR__;
require_once dirname(__DIR__, 4) . '/vendor/autoload.php';
require_once dirname(__DIR__, 4) . '/vendor/yiisoft/yii2/Yii.php';

use hiapi\r01\R01Tool;

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$tool = new R01Tool(new stdClass(), [
    'url'       => $_ENV['R01_URL'],
    'login'     => $_ENV['R01_LOGIN'],
    'password'  => $_ENV['R01_PASSWORD'],
]);

$res = $tool->domainTransfer([
    'domain'    => 'silverfires1.me',
    'password'  => 'adf-AA01',
    'period'    => 1,
    'roid'      => 'D425500000000823001-AGRS',
]);

var_dump($res);
