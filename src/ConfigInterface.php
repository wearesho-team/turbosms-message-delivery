<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms;

interface ConfigInterface
{
    public const URI = 'https://api.turbosms.ua/';
    public const SENDER = 'Msg';

    public function getHttpToken(): string;

    public function getSenderName(): string;
}
