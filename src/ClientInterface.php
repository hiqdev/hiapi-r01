<?php
/**
 * hiAPI R01 plugin
 *
 * @link      https://github.com/hiqdev/hiapi-heppy
 * @package   hiapi-r01
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\r01;

/**
 * R01 connection interface.
 */
interface ClientInterface
{
    public function request(string $command, array $data): array;
}
