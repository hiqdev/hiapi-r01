<?php
/**
 * hiAPI r01 plugin
 *
 * @link      https://github.com/hiqdev/hiapi-r01
 * @package   hiapi-r01
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\r01;

use hiapi\r01\exceptions\InvalidCallException;
use hiapi\r01\modules\AbstractModule;

class R01Tool
{
    private $client;

    private $base;

    private $data;

    protected $modules = [
        'domain'    => DomainModule::class,
        'domains'   => DomainModule::class,
        'contact'   => ContactModule::class,
        'contacts'  => ContactModule::class,
        'host'      => HostModule::class,
        'hosts'     => HostModule::class,
    ];

    public function __construct($base, $data)
    {
        $this->base = $base;
        $this->data = $data;
        ini_set('soap.wsdl_cache_enabled',0);
    }

    public function __call($command, $args): array
    {
        $this->getClient();

        $moduleName = reset(preg_split('/(?=[A-Z])/', $command));
        $module = $this->getModule($moduleName);

        return call_user_func_array([$module, $command], $args);
    }

    /**
     * @param string $name
     * @return AbstractModule
     */
    public function getModule(string $name): AbstractModule
    {
        if (empty($this->modules[$name])) {
            throw new InvalidCallException("module `$name` not found");
        }
        $module = $this->modules[$name];
        if (!is_object($module)) {
            $this->modules[$name] = $this->createModule($module);
        }

        return $this->modules[$name];
    }

    /**
     * @param string $class
     * @return AbstractModule
     */
    public function createModule(string $class): AbstractModule
    {
        return new $class($this);
    }

    /**
     * @return ClientInterface
     * @throws exceptions\LoginFailedException
     */
    protected function getClient(): ClientInterface
    {
        if ($this->client === null) {
            $this->client = new R01SoapClient(
                $this->data['url'],
                $this->data['login'],
                $this->data['password']
            );
        }

        return $this->client;
    }
}
