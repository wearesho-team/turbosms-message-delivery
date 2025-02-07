<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms;

class ResponseException extends Exception
{

    public const STATUS_INVALID_JSON = 'InvalidJson';
    public const CODE_INVALID_JSON = -1;

    public const STATUS_MISSING_FIELD_CODE = 'ResponseMissingFieldCode';
    public const CODE_MISSING_FIELD_CODE = -101;

    public const STATUS_MISSING_FIELD_STATUS = 'ResponseMissingFieldStatus';
    public const CODE_MISSING_FIELD_STATUS = -102;
    public const STATUS_MISSING_FIELD_RESULT = 'ResponseMissingFieldResult';
    public const CODE_MISSING_FIELD_RESULT = -103;

    // region Balance
    public const STATUS_MISSING_FIELD_RESULT_BALANCE = 'ResponseMissingFieldResultBalance';
    public const CODE_MISSING_FIELD_RESULT_BALANCE = -1001;
    // endregion Balance

    public function __construct(string $responseStatus, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($responseStatus, $code, $previous);
    }
}
