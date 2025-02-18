<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms;

class Response
{
    public const CODE_SUCCESS = 0;
    public const CODE_PONG = 1;
    public const CODE_SUCCESS_MESSAGE_ACCEPTED = 0;
    public const CODE_SUCCESS_MESSAGE_SENT = 801;
    public const CODE_SUCCESS_MESSAGE_PARTIAL_ACCEPTED = 802;
    public const CODE_SUCCESS_MESSAGE_PARTIAL_SENT = 803;

    protected const RESPONSE_KEY_CODE = 'response_code';
    protected const RESPONSE_KEY_STATUS = 'response_status';
    protected const RESPONSE_KEY_RESULT = 'response_result';

    private static array $exceptionMap = [
        self::RESPONSE_KEY_CODE => [
            ResponseException::STATUS_MISSING_FIELD_CODE,
            ResponseException::CODE_MISSING_FIELD_CODE
        ],
        self::RESPONSE_KEY_STATUS => [
            ResponseException::STATUS_MISSING_FIELD_STATUS,
            ResponseException::CODE_MISSING_FIELD_STATUS
        ],
        self::RESPONSE_KEY_RESULT => [
            ResponseException::STATUS_MISSING_FIELD_RESULT,
            ResponseException::CODE_MISSING_FIELD_RESULT
        ],
    ];

    private static array $successCodes = [
        self::CODE_SUCCESS,
        self::CODE_PONG,
        self::CODE_SUCCESS_MESSAGE_ACCEPTED,
        self::CODE_SUCCESS_MESSAGE_SENT,
        self::CODE_SUCCESS_MESSAGE_PARTIAL_ACCEPTED,
        self::CODE_SUCCESS_MESSAGE_PARTIAL_SENT,
    ];

    public function __construct(
        public readonly int $code,
        public readonly string $status,
        public readonly ?array $result = null
    ) {
    }

    public function isSuccess(): bool
    {
        return in_array($this->code, self::$successCodes, true);
    }

    /**
     * @param string $response
     * @return static
     * @throws ResponseException
     */
    public static function parse(string $response): static
    {
        $responseData = static::parseRawResponse($response);
        return static::fromArray($responseData);
    }

    public static function fromArray(array $responseData): static
    {
        static::validateArrayResponse($responseData);
        return new static(
            $responseData[self::RESPONSE_KEY_CODE],
            $responseData[self::RESPONSE_KEY_STATUS],
            $responseData[self::RESPONSE_KEY_RESULT]
        );
    }

    private static function parseRawResponse(string $response): array
    {
        try {
            return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ResponseException(
                ResponseException::STATUS_INVALID_JSON,
                ResponseException::CODE_INVALID_JSON,
                $exception
            );
        }
    }

    protected static function validateArrayResponse(array $responseData): void
    {
        $missingKeys = array_diff(
            array_keys(self::$exceptionMap),
            array_keys($responseData)
        );

        if (!empty($missingKeys)) {
            throw new ResponseException(
                ...self::$exceptionMap[reset($missingKeys)]
            );
        }
    }
}
