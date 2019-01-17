<?php

namespace Wearesho\Delivery\TurboSms;

use GuzzleHttp;

/**
 * Class CookiesDisabledException
 * @package Wearesho\Delivery\TurboSms
 */
class CookiesDisabledException extends \DomainException
{
    /** @var GuzzleHttp\ClientInterface */
    protected $client;

    public function __construct(
        GuzzleHttp\ClientInterface $client,
        string $message = "",
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->client = $client;
    }

    public function getClient(): GuzzleHttp\ClientInterface
    {
        return $this->client;
    }
}
