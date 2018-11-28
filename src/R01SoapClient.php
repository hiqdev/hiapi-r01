<?php

namespace hiapi\r01;

use hiapi\r01\exceptions\FailedCommandException;
use hiapi\r01\exceptions\FailedLoginException;
use SoapClient;
use SoapFault;

class R01SoapClient implements ClientInterface
{
    private $soapClient;

    private $login;

    private $password;

    private $isConnected;

    /**
     * R01SoapClient constructor.
     * @param string $url
     * @param string $login
     * @param string $password
     */
    public function __construct(string $url, string $login, string $password)
    {
        $this->login    = $login;
        $this->password = $password;

        $this->soapClient = new SoapClient(null, [
            'location'   => $url,
            'uri'        => 'urn:RegbaseSoapInterface',
            'user_agent' => 'RegbaseSoapInterfaceClient',
            'exceptions' => true,
            'trace'      => 1,
        ]);
    }

    /**
     * @param string $command
     * @param array $data
     * @return array
     * @throws FailedCommandException
     * @throws FailedLoginException
     */
    public function request(string $command, array $data): array
    {
        $this->logIn();

        try {
            $result = $this->soapClient->__soapCall($command, $data);
        } catch (SoapFault $fault) {
            throw new FailedCommandException("Failed to perform command $command");
        }

        return json_decode(json_encode($result), true);
    }

    /**
     * @throws FailedLoginException
     */
    private function logIn(): void
    {
        if ($this->isConnected) {
            return;
        }

        try {
            $result = $this->soapClient->logIn($this->login, $this->password);
        } catch(SoapFault $fault) {
            throw new FailedLoginException();
        }

        $this->isConnected = true;
        $this->soapClient->__setCookie('SOAPClient', $result->status->message);
    }

    private function logOut()
    {
        $this->soapClient->logOut();
        $this->isConnected = false;
    }

    public function __destruct()
    {
        $this->logOut();
    }
}
