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
use hiapi\r01\modules\ContactModule;
use hiapi\r01\modules\DomainModule;
use hiapi\r01\modules\PollModule;

class R01Tool
{
    private $client;

    private $base;

    private $data;

    private $defaultNss = ['ns1.topdns.me', 'ns2.topdns.me'];

    protected $modules = [
        'domain'    => DomainModule::class,
        'domains'   => DomainModule::class,
        'contact'   => ContactModule::class,
        'contacts'  => ContactModule::class,
        'host'      => HostModule::class,
        'hosts'     => HostModule::class,
        'poll'      => PollModule::class,
        'polls'     => PollModule::class,
    ];

    public function __construct($base, $data)
    {
        $this->base = $base;
        $this->data = $data;
        ini_set('soap.wsdl_cache_enabled',0);
    }

    public function __call($command, $args): array
    {
        $moduleName = reset(preg_split('/(?=[A-Z])/', $command));
        $module = $this->getModule($moduleName);

        return call_user_func_array([$module, $command], $args);
    }

    /**
     * @param string $name
     * @return AbstractModule
     */
    private function getModule(string $name): AbstractModule
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
    private function createModule(string $class): AbstractModule
    {
        return new $class($this);
    }

    /**
     * @return array
     */
    public function getDefaultNss(): array
    {
        return $this->defaultNss;
    }

    /**
     * @return ClientInterface
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

    /**
     * @param string $command
     * @param array $input
     * @param array $returns
     * @return array
     */
    public function request(string $command, array $input, array $returns = []): array
    {
        try {
            $response = $this->getClient()->request($command, $input);
        } catch (\Throwable $e) {
            return array_merge($input, [
                '_error' => $e->getMessage(),
            ]);
        }

        if (empty($returns)) {
            return $response;
        }

        $result = [];
        foreach ($returns as $apiName => $eppName) {
            $result[$apiName] = $response[$eppName];
        }
        $result['status'] = $response['status'];

        return $result;
    }

    public function getBase()
    {
        return $this->base;
    }

    public function getContactTypes() : array
    {
        return ['registrant'];
    }
}
