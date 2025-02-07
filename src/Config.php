<?php

namespace Wearesho\Delivery\TurboSms;

class Config implements ConfigInterface
{
    public function __construct(
        protected readonly string $httpToken,
        protected readonly string $senderName = ConfigInterface::SENDER,
    ) {
    }

    public function getHttpToken(): string
    {
        return $this->httpToken;
    }

    public function getSenderName(): string
    {
        return $this->senderName;
    }
}
