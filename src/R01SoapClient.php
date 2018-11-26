<?php

namespace hiapi\r01;

use hiapi\r01\exceptions\LoginFailedException;
use SoapClient;

class R01SoapClient implements ClientInterface
{
    private $soapClient;

    /**
     * R01SoapClient constructor.
     * @param string $url
     * @param string $login
     * @param string $password
     * @throws LoginFailedException
     */
    public function __construct(string $url, string $login, string $password)
    {
        $this->soapClient = new SoapClient(null, [
            'location'   => $url,
            'uri'        => 'urn:RegbaseSoapInterface',
            'user_agent' => 'RegbaseSoapInterfaceClient',
            'exceptions' => false,
            'trace'      => 1,
        ]);

        $this->logIn($login, $password);

        return $this->soapClient;
    }

    /**
     * @param string $login
     * @param string $password
     * @throws LoginFailedException
     */
    private function logIn(string $login, string $password): void
    {
        $result = $this->soapClient->logIn($login, $password);

        if ($result->status->code !== 1) {
            throw new LoginFailedException("logIn failed with reason: `$result->faultstring`");
        }
    }

    public function request(array $data): array
    {
        // TODO: Implement request() method.
    }
}
