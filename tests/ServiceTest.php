<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms\Tests;

use GuzzleHttp;
use PHPUnit\Framework\TestCase;
use Wearesho\Delivery;
use Wearesho\Delivery\TurboSms;

class ServiceTest extends TestCase
{
    public function balanceDataProvider(): array
    {
        return [
            [
                new GuzzleHttp\Psr7\Response(200, [], '{
   "response_code": 105,
   "response_status": "REQUIRED_AUTH",
   "response_result": null
}'),
                null,
                new TurboSms\Exception('REQUIRED_AUTH', 105)
            ],
            [
                new GuzzleHttp\Psr7\Response(200, [], '{
   "response_status": "REQUIRED_AUTH",
   "response_result": null
}'),
                null,
                new TurboSms\ResponseException(
                    TurboSms\ResponseException::STATUS_MISSING_FIELD_CODE,
                    TurboSms\ResponseException::CODE_MISSING_FIELD_CODE
                )
            ],
            [
                new GuzzleHttp\Psr7\Response(200, [], '{
   "response_code": 105,
   "response_status": "REQUIRED_AUTH"
}'),
                null,
                new TurboSms\ResponseException(
                    TurboSms\ResponseException::STATUS_MISSING_FIELD_RESULT,
                    TurboSms\ResponseException::CODE_MISSING_FIELD_RESULT
                )
            ],
            [
                new GuzzleHttp\Psr7\Response(200, [], '{
   "response_code": 105,
   "response_result": null
}'),
                null,
                new TurboSms\ResponseException(
                    TurboSms\ResponseException::STATUS_MISSING_FIELD_STATUS,
                    TurboSms\ResponseException::CODE_MISSING_FIELD_STATUS
                )
            ],
            [
                new GuzzleHttp\Psr7\Response(200, [], '{
   Obviously Absolutely Invalid Json
}'),
                null,
                new TurboSms\ResponseException(
                    TurboSms\ResponseException::STATUS_INVALID_JSON,
                    TurboSms\ResponseException::CODE_INVALID_JSON
                )
            ],
            [
                new GuzzleHttp\Psr7\Response(200, [], '{
   "response_code": 0,
   "response_status": "OK",
   "response_result": {
        "balance": 105.01
   }
}'),
                new TurboSms\BalanceResponse(0, 'OK', [
                    'balance' => 105.01,
                ]),
            ],
            [
                new GuzzleHttp\Psr7\Response(200, [], '{
   "response_code": 0,
   "response_status": "OK",
   "response_result": {
        "invalidBalanceKey": 105.01
   }
}'),
                null,
                new TurboSms\ResponseException(
                    TurboSms\ResponseException::STATUS_MISSING_FIELD_RESULT_BALANCE,
                    TurboSms\ResponseException::CODE_MISSING_FIELD_RESULT_BALANCE
                )
            ]
        ];
    }

    /**
     * @dataProvider balanceDataProvider
     */
    public function testBalance(
        \Psr\Http\Message\ResponseInterface $response,
        ?TurboSms\BalanceResponse $expectedResponse = null,
        ?Delivery\Exception $expectedException = null,
    ): void {
        $config = new TurboSms\Config($httpToken = (string)time());

        $client = $this->createMock(GuzzleHttp\Client::class);
        $client->method('request')
            ->with(
                'POST',
                'https://api.turbosms.ua/user/balance.json',
                [
                    GuzzleHttp\RequestOptions::HEADERS => [
                        'Authorization' => 'Basic ' . $httpToken,
                    ]
                ]
            )->willReturn($response);

        $service = new TurboSms\Service($client, $config);
        if (!is_null($expectedException)) {
            $this->expectException(get_class($expectedException));
            $this->expectExceptionCode($expectedException->getCode());
            $this->expectDeprecationMessage($expectedException->getMessage());
        }
        $response = $service->balance();
        if (!is_null($expectedResponse)) {
            $this->assertEquals($expectedResponse, $response);
        }
    }
}
