<?php

namespace Wearesho\Delivery\TurboSms;

use Horat1us\Environment;

/**
 * Class EnvironmentConfig
 * @package Wearesho\Delivery\TurboSms
 */
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
        $this->getEnv('URI', ConfigInterface::URI);
    }
}
