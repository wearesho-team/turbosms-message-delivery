<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms;

class Response
{
    public const CODE_SUCCESS = 0;

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
        ]
    ];

    public function __construct(
        public readonly int    $code,
        public readonly string $status,
        public readonly ?array  $result = null
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->code === self::CODE_SUCCESS;
    }

    /**
     * @param string $response
     * @return self
     * @throws ResponseException
     */
    public function parse(string $response): self
    {
        $responseData = $this->parseRawResponse($response);
        $this->validateArrayResponse($responseData);
        return new self(
            $responseData[self::RESPONSE_KEY_CODE],
            $responseData[self::RESPONSE_KEY_STATUS],
            $responseData[self::RESPONSE_KEY_RESULT]
        );
    }

    private function parseRawResponse(string $response): array
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

    protected function validateArrayResponse(array $responseData): void
    {
        $missingKeys = array_diff(
            array_keys(self::$exceptionMap),
            array_keys($responseData)
        );

        if (!empty($missingKeys)) {
            throw new ResponseException(
                ...self::$exceptionMap[array_key_first($missingKeys)]
            );
        }
    }
}
