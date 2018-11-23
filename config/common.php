<?php
/**
 * hiAPI hEPPy plugin
 *
 * @link      https://github.com/hiqdev/hiapi-r01
 * @package   hiapi-r01
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

return [
    'container' => [
        'singletons' => [
            'r01Tool' => [
                '__class' => \hiapi\r01\R01Tool::class,
            ],
            \hiapi\r01\ClientInterface::class => [
                '__class' => \hiapi\r01\RabbitMQClient::class,
            ],
        ],
    ],
];
