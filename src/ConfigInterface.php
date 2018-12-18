<?php

namespace Wearesho\Delivery\TurboSms;

/**
 * Interface ConfigInterface
 * @package Wearesho\Delivery\TurboSms
 */
interface ConfigInterface
{
    public const URI = 'http://turbosms.in.ua/api/wsdl.html';

    public function getLogin(): string;

    public function getPassword(): string;

    public function getUri(): string;
}
