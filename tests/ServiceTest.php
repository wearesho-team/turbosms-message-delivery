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

    public function viberOptionsDataProvider(): array
    {
        return [
            'viber with all options' => [
                'viber',
                [
                    'viberImageUrl' => 'https://example.com/image.jpg',
                    'viberButtonText' => 'Click me',
                    'viberButtonUrl' => 'https://example.com/action',
                ],
                [
                    'recipients' => ['+380970000000'],
                    'viber' => [
                        'sender' => 'TestSender',
                        'text' => 'Test message',
                        'image_url' => 'https://example.com/image.jpg',
                        'caption' => 'Click me',
                        'action' => 'https://example.com/action',
                    ],
                ],
            ],
            'viber with image only' => [
                'viber',
                [
                    'viberImageUrl' => 'https://example.com/image.jpg',
                ],
                [
                    'recipients' => ['+380970000000'],
                    'viber' => [
                        'sender' => 'TestSender',
                        'text' => 'Test message',
                        'image_url' => 'https://example.com/image.jpg',
                    ],
                ],
            ],
            'viber with button options only' => [
                'viber',
                [
                    'viberButtonText' => 'Click me',
                    'viberButtonUrl' => 'https://example.com/action',
                ],
                [
                    'recipients' => ['+380970000000'],
                    'viber' => [
                        'sender' => 'TestSender',
                        'text' => 'Test message',
                        'caption' => 'Click me',
                        'action' => 'https://example.com/action',
                    ],
                ],
            ],
            'viber without options' => [
                'viber',
                [],
                [
                    'recipients' => ['+380970000000'],
                    'viber' => [
                        'sender' => 'TestSender',
                        'text' => 'Test message',
                    ],
                ],
            ],
            'sms only with viber options provided' => [
                'sms',
                [
                    'viberImageUrl' => 'https://example.com/image.jpg',
                    'viberButtonText' => 'Click me',
                    'viberButtonUrl' => 'https://example.com/action',
                ],
                [
                    'recipients' => ['+380970000000'],
                    'sms' => [
                        'sender' => 'TestSender',
                        'text' => 'Test message',
                    ],
                ],
            ],
            'multi-channel with viber options' => [
                ['sms', 'viber'],
                [
                    'viberImageUrl' => 'https://example.com/image.jpg',
                    'viberButtonText' => 'Click me',
                    'viberButtonUrl' => 'https://example.com/action',
                ],
                [
                    'recipients' => ['+380970000000'],
                    'sms' => [
                        'sender' => 'TestSender',
                        'text' => 'Test message',
                    ],
                    'viber' => [
                        'sender' => 'TestSender',
                        'text' => 'Test message',
                        'image_url' => 'https://example.com/image.jpg',
                        'caption' => 'Click me',
                        'action' => 'https://example.com/action',
                    ],
                ],
            ],
            'multi-channel without viber options' => [
                ['sms', 'viber'],
                [],
                [
                    'recipients' => ['+380970000000'],
                    'sms' => [
                        'sender' => 'TestSender',
                        'text' => 'Test message',
                    ],
                    'viber' => [
                        'sender' => 'TestSender',
                        'text' => 'Test message',
                    ],
                ],
            ],
            'viber with empty string filtering' => [
                'viber',
                [
                    'viberImageUrl' => '',
                    'viberButtonText' => 'Click me',
                    'viberButtonUrl' => '',
                ],
                [
                    'recipients' => ['+380970000000'],
                    'viber' => [
                        'sender' => 'TestSender',
                        'text' => 'Test message',
                        'caption' => 'Click me',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider viberOptionsDataProvider
     */
    public function testSendWithViberOptions(
        string|array $channel,
        array $viberOptions,
        array $expectedRequest
    ): void {
        $config = new TurboSms\Config('httpToken', 'TestSender');

        $client = $this->createMock(GuzzleHttp\Client::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->stringContains('message/send.json'),
                $this->callback(function ($options) use ($expectedRequest) {
                    $this->assertArrayHasKey(GuzzleHttp\RequestOptions::JSON, $options);
                    $actualRequest = $options[GuzzleHttp\RequestOptions::JSON];

                    $this->assertEquals(
                        $expectedRequest['recipients'],
                        $actualRequest['recipients'],
                        'Recipients should match'
                    );

                    if (isset($expectedRequest['sms'])) {
                        $this->assertArrayHasKey('sms', $actualRequest, 'SMS key should exist');
                        $this->assertEquals(
                            $expectedRequest['sms'],
                            $actualRequest['sms'],
                            'SMS structure should match'
                        );
                    } else {
                        $this->assertArrayNotHasKey('sms', $actualRequest, 'SMS key should not exist');
                    }

                    if (isset($expectedRequest['viber'])) {
                        $this->assertArrayHasKey('viber', $actualRequest, 'Viber key should exist');
                        $this->assertEquals(
                            $expectedRequest['viber'],
                            $actualRequest['viber'],
                            'Viber structure should match'
                        );
                    } else {
                        $this->assertArrayNotHasKey('viber', $actualRequest, 'Viber key should not exist');
                    }

                    return true;
                })
            )
            ->willReturn(new GuzzleHttp\Psr7\Response(200, [], '{
                "response_code": 0,
                "response_status": "OK",
                "response_result": {
                    "+380970000000": {
                        "message_id": "test-id",
                        "response_status": "OK"
                    }
                }
            }'));

        $service = new TurboSms\Service($client, $config);

        $message = new Delivery\Message(
            'Test message',
            '+380970000000',
            array_merge(['channel' => $channel], $viberOptions)
        );

        $result = $service->send($message);

        $this->assertInstanceOf(Delivery\ResultInterface::class, $result);
    }

    public function testBatchWithViberOptions(): void
    {
        $config = new TurboSms\Config('httpToken', 'TestSender');

        $client = $this->createMock(GuzzleHttp\Client::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->stringContains('message/sendmulti.json'),
                $this->callback(function ($options) {
                    $this->assertArrayHasKey(GuzzleHttp\RequestOptions::JSON, $options);
                    $actualRequest = $options[GuzzleHttp\RequestOptions::JSON];

                    $this->assertIsArray($actualRequest);
                    $this->assertCount(2, $actualRequest);

                    $keys = array_keys($actualRequest);

                    $firstRequest = $actualRequest[$keys[0]];
                    $this->assertEquals(['+380970000001'], $firstRequest['recipients']);
                    $this->assertArrayHasKey('viber', $firstRequest);
                    $this->assertEquals('TestSender', $firstRequest['viber']['sender']);
                    $this->assertEquals('Message with Viber options', $firstRequest['viber']['text']);
                    $this->assertEquals('https://example.com/image.jpg', $firstRequest['viber']['image_url']);
                    $this->assertEquals('Click me', $firstRequest['viber']['caption']);
                    $this->assertArrayNotHasKey('sms', $firstRequest);

                    $secondRequest = $actualRequest[$keys[1]];
                    $this->assertEquals(['+380970000002'], $secondRequest['recipients']);
                    $this->assertArrayHasKey('sms', $secondRequest);
                    $this->assertArrayHasKey('viber', $secondRequest);
                    $this->assertEquals('TestSender', $secondRequest['sms']['sender']);
                    $this->assertEquals('Multi-channel message', $secondRequest['sms']['text']);
                    $this->assertEquals('TestSender', $secondRequest['viber']['sender']);
                    $this->assertEquals('Multi-channel message', $secondRequest['viber']['text']);
                    $this->assertEquals('https://example.com/button.jpg', $secondRequest['viber']['image_url']);
                    $this->assertArrayNotHasKey('image_url', $secondRequest['sms']);

                    return true;
                })
            )
            ->willReturn(new GuzzleHttp\Psr7\Response(200, [], '{
                "response_code": 0,
                "response_status": "OK",
                "response_result": {
                    "i_' . time() . '_0": {
                        "response_code": 0,
                        "response_status": "OK",
                        "response_result": {
                            "+380970000001": {
                                "message_id": "test-id-1",
                                "response_status": "OK"
                            }
                        }
                    },
                    "i_' . time() . '_1": {
                        "response_code": 0,
                        "response_status": "OK",
                        "response_result": {
                            "+380970000002": {
                                "message_id": "test-id-2",
                                "response_status": "OK"
                            }
                        }
                    }
                }
            }'));

        $service = new TurboSms\Service($client, $config);

        $messages = [
            new Delivery\Message(
                'Message with Viber options',
                '+380970000001',
                [
                    'channel' => 'viber',
                    'viberImageUrl' => 'https://example.com/image.jpg',
                    'viberButtonText' => 'Click me',
                ]
            ),
            new Delivery\Message(
                'Multi-channel message',
                '+380970000002',
                [
                    'channel' => ['sms', 'viber'],
                    'viberImageUrl' => 'https://example.com/button.jpg',
                ]
            ),
        ];

        $results = iterator_to_array($service->batch($messages));

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertInstanceOf(Delivery\ResultInterface::class, $result);
        }
    }
}
