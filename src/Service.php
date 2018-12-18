<?php

namespace Wearesho\Delivery\TurboSms;

use GuzzleHttp\ClientInterface;
use Meng\AsyncSoap;
use Wearesho\Delivery;

/**
 * Class Service
 * @package Wearesho\Delivery\TurboSms
 */
class Service implements Delivery\ServiceInterface
{
    /** @var ConfigInterface */
    protected $config;

    /** @var ClientInterface */
    protected $client;

    /** @var AsyncSoap\SoapClientInterface */
    private $soap;

    public function __construct(ConfigInterface $config, ClientInterface $client)
    {
        $this->config = $config;
        $this->client = $client;
        $this->soap = (new AsyncSoap\Guzzle\Factory())->create()
    }

    public function send(Delivery\MessageInterface $message): void
    {
        $client = (new AsyncSoap\Guzzle\Factory())->create(
            $this->client,
            $this->config->getUri()
        );

        $result = $client->call('Auth', [[
            'login' => $this->getConfig()->getLogin(),
            'password' => $this->getConfig()->getPassword(),
        ]]);

        if ($result->AuthResult !== "Вы успешно авторизировались") {
            throw new Delivery\Exception($result->AuthResult);
        }
    }

    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    protected function soap(): AsyncSoap\SoapClientInterface
    {
        return (new AsyncSoap\Guzzle\Factory())
    }
}
