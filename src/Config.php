<?php

namespace Wearesho\Delivery\TurboSms;

class Config implements ConfigInterface
{
    protected string $login;

    protected string $password;

    protected string $senderName;

    protected string $uri;

    public function __construct(
        string $login,
        string $password,
        string $sender = ConfigInterface::SENDER,
        string $uri = ConfigInterface::URI
    ) {
        $this->login = $login;
        $this->password = $password;
        $this->senderName = $sender;
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

    public function getSenderName(): string
    {
        return $this->senderName;
    }
}
