<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms;

use Horat1us\Environment;

class EnvironmentConfig extends Environment\Config implements ConfigInterface
{
    public function __construct(string $keyPrefix = 'TURBOSMS_')
    {
        parent::__construct($keyPrefix);
    }

    public function getLogin(): string
    {
        return $this->getEnv('LOGIN');
    }

    public function getPassword(): string
    {
        return $this->getEnv('PASSWORD');
    }

    public function getUri(): string
    {
        return $this->getEnv('URI', ConfigInterface::URI);
    }

    public function getSenderName(): string
    {
        return $this->getEnv('SENDER', ConfigInterface::SENDER);
    }
}
