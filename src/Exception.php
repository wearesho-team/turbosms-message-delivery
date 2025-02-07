<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms;

use Wearesho\Delivery;

class Exception extends Delivery\Exception
{
    public function __construct(public readonly string $responseStatus, int $code = 0, \Throwable $previous = null)
    {
        $message = "Request failed with status: " . $responseStatus;
        parent::__construct($message, $code, $previous);
    }
}
