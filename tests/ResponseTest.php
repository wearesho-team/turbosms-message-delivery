<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms\Tests;

use PHPUnit\Framework\TestCase;
use Wearesho\Delivery\TurboSms;

class ResponseTest extends TestCase
{
    /**
     * Codes and statuses are taken from the table at https://turbosms.ua/ua/api.html
     */
    public function successCodeDataProvider(): array
    {
        return [
            'OK' => [0, true],
            'PONG' => [1, true],
            'SUCCESS_MESSAGE_ACCEPTED' => [800, true],
            'SUCCESS_MESSAGE_SENT' => [801, true],
            'SUCCESS_MESSAGE_PARTIAL_ACCEPTED' => [802, true],
            'SUCCESS_MESSAGE_PARTIAL_SENT' => [803, true],
            'SUCCESS_PROCESSED' => [804, true],
            'SUCCESS_PARTIAL_PROCESSED' => [805, true],
            'REQUIRED_AUTH' => [105, false],
            'REQUIRED_BALANCE' => [203, false],
            'NOT_ALLOWED_RECIPIENT_COUNTRY' => [406, false],
            'FAILED_DUPLICATE_REQUEST' => [507, false],
            'FATAL_ERROR' => [999, false],
        ];
    }

    /**
     * @dataProvider successCodeDataProvider
     */
    public function testIsSuccessCode(int $code, bool $expected): void
    {
        $this->assertSame($expected, TurboSms\Response::isSuccessCode($code));
    }

    /**
     * @dataProvider successCodeDataProvider
     */
    public function testIsSuccess(int $code, bool $expected): void
    {
        $response = new TurboSms\Response($code, 'STATUS');

        $this->assertSame($expected, $response->isSuccess());
    }
}
