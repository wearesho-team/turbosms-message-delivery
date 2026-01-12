<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms;

interface ConfigInterface
{
    public const SENDER = 'Msg';

    public function getHttpToken(): string;

    public function getSenderName(): string;

    public function getViberSenderName(): ?string;
}
