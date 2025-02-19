<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms;

use Wearesho\Delivery;

class BalanceResponse extends Response implements Delivery\BalanceInterface, \Stringable
{
    private readonly float $balance;

    private const RESPONSE_KEY_RESULT_BALANCE = 'balance';

    public function __construct(int $code, string $status, ?array $result = null)
    {
        parent::__construct($code, $status, $result);
        $this->balance = ($this->isSuccess() && is_array($result))
            ? $result[self::RESPONSE_KEY_RESULT_BALANCE]
            : 0;
    }

    public function getAmount(): float
    {
        return $this->balance;
    }

    public function getCurrency(): ?string
    {
        return "UAH";
    }

    protected static function validateArrayResponse(array $responseData): void
    {
        parent::validateArrayResponse($responseData);
        if (
            is_array($responseData[self::RESPONSE_KEY_RESULT])
            && !array_key_exists(self::RESPONSE_KEY_RESULT_BALANCE, $responseData[self::RESPONSE_KEY_RESULT])
        ) {
            throw new ResponseException(
                ResponseException::STATUS_MISSING_FIELD_RESULT_BALANCE,
                ResponseException::CODE_MISSING_FIELD_RESULT_BALANCE
            );
        }
    }

    public function __toString(): string
    {
        return number_format($this->balance, 2, '.', '')
            . ' ' . $this->getCurrency();
    }
}
