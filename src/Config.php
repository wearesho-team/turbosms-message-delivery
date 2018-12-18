<?php

namespace Wearesho\Delivery\TurboSms;

/**
 * Class Config
 * @package Wearesho\Delivery\TurboSms
 */
class Config implements ConfigInterface
{
    /** @var string */
    protected $login;

    /** @var string */
    protected $password;

    /** @var string */
    protected $uri;

    public function __construct(string $login, string $password, string $uri = ConfigInterface::URI)
    {
        $this->login = $login;
        $this->password = $password;
        $this->uri = $uri;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUri(): string
    {
        return $this->uri;
    }
}
