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

    public function getHttpToken(): string
    {
        return $this->getEnv('HTTP_TOKEN');
    }

    public function getSenderName(): string
    {
        return $this->getEnv('SENDER', ConfigInterface::SENDER);
    }
}
