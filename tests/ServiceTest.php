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

    /**
     * The documented shape (https://turbosms.ua/ua/api.html) is the first case: message/send.json
     * returns response_result as a list of per-recipient objects, each carrying
     * phone/response_code/message_id. The remaining cases are payloads the docs do not describe,
     * covering the defensive branches: a per-recipient error alongside a message_id (the docs pair
     * an error with message_id: null), a response_result keyed by phone rather than a list, and an
     * absent response_status.
     */
    public function sendResultDataProvider(): array
    {
        return [
            'documented success, top level 800' => [
                '{
                    "response_code": 800,
                    "response_status": "SUCCESS_MESSAGE_ACCEPTED",
                    "response_result": [
                        {
                            "phone": "+380970000000",
                            "response_code": 0,
                            "message_id": "f83f8868-5e46-c6cf-e4fb-615e5a293754",
                            "response_status": "OK"
                        }
                    ]
                }',
                'f83f8868-5e46-c6cf-e4fb-615e5a293754',
                Delivery\Result\Status::Accepted,
                'OK',
            ],
            'undocumented: recipient error alongside a message id is failed' => [
                '{
                    "response_code": 802,
                    "response_status": "SUCCESS_MESSAGE_PARTIAL_ACCEPTED",
                    "response_result": [
                        {
                            "phone": "+380970000000",
                            "response_code": 427,
                            "message_id": "f83f8868-5e46-c6cf-e4fb-615e5a293754",
                            "response_status": "NOT_ALLOWED_MESSAGE_DUPLICATE"
                        }
                    ]
                }',
                'f83f8868-5e46-c6cf-e4fb-615e5a293754',
                Delivery\Result\Status::Failed,
                'NOT_ALLOWED_MESSAGE_DUPLICATE',
            ],
            'undocumented: phone keyed result without response_code stays accepted' => [
                '{
                    "response_code": 0,
                    "response_status": "OK",
                    "response_result": {
                        "+380970000000": {
                            "message_id": "test-id",
                            "response_status": "OK"
                        }
                    }
                }',
                'test-id',
                Delivery\Result\Status::Accepted,
                'OK',
            ],
            'missing response_status yields null reason' => [
                '{
                    "response_code": 0,
                    "response_status": "OK",
                    "response_result": [
                        {
                            "phone": "+380970000000",
                            "response_code": 0,
                            "message_id": "test-id"
                        }
                    ]
                }',
                'test-id',
                Delivery\Result\Status::Accepted,
                null,
            ],
        ];
    }

    /**
     * @dataProvider sendResultDataProvider
     */
    public function testSendResult(
        string $body,
        string $expectedMessageId,
        Delivery\Result\Status $expectedStatus,
        ?string $expectedReason
    ): void {
        $result = $this->send($body);

        $this->assertSame($expectedMessageId, $result->messageId());
        $this->assertSame($expectedStatus, $result->status());
        $this->assertSame($expectedReason, $result->reason());
    }

    /**
     * Documented shapes: the two `null message id` cases, where a rejected recipient carries
     * message_id: null while response_code/response_status describe the error, and `empty result`,
     * since response_result may be null. The rest are payloads the docs do not describe — an omitted
     * message_id key rather than a null one, a detail-less entry, a scalar entry — covering the
     * defensive branches.
     */
    public function sendFailureDataProvider(): array
    {
        return [
            'null message id under partial success' => [
                '{
                    "response_code": 802,
                    "response_status": "SUCCESS_MESSAGE_PARTIAL_ACCEPTED",
                    "response_result": [
                        {
                            "phone": "+380970000000",
                            "response_code": 406,
                            "message_id": null,
                            "response_status": "NOT_ALLOWED_RECIPIENT_COUNTRY"
                        }
                    ]
                }',
                406,
                'Failed to get messageId for recipient +380970000000: '
                . 'NOT_ALLOWED_RECIPIENT_COUNTRY (406), '
                . 'response status SUCCESS_MESSAGE_PARTIAL_ACCEPTED (802)',
            ],
            'null message id under top level failure' => [
                '{
                    "response_code": 404,
                    "response_status": "NOT_ALLOWED_NUMBER_STOPLIST",
                    "response_result": [
                        {
                            "phone": "+380970000000",
                            "response_code": 404,
                            "message_id": null,
                            "response_status": "NOT_ALLOWED_NUMBER_STOPLIST"
                        }
                    ]
                }',
                404,
                'Failed to get messageId for recipient +380970000000: '
                . 'NOT_ALLOWED_NUMBER_STOPLIST (404), '
                . 'response status NOT_ALLOWED_NUMBER_STOPLIST (404)',
            ],
            'message id key absent' => [
                '{
                    "response_code": 203,
                    "response_status": "REQUIRED_BALANCE",
                    "response_result": [
                        {
                            "phone": "+380970000000",
                            "response_code": 203,
                            "response_status": "REQUIRED_BALANCE"
                        }
                    ]
                }',
                203,
                'Failed to get messageId for recipient +380970000000: '
                . 'REQUIRED_BALANCE (203), '
                . 'response status REQUIRED_BALANCE (203)',
            ],
            'result entry without any detail' => [
                '{
                    "response_code": 999,
                    "response_status": "FATAL_ERROR",
                    "response_result": [
                        []
                    ]
                }',
                999,
                'Failed to get messageId for recipient +380970000000: '
                . 'unknown status (no code), '
                . 'response status FATAL_ERROR (999)',
            ],
            'empty result' => [
                '{
                    "response_code": 105,
                    "response_status": "REQUIRED_AUTH",
                    "response_result": []
                }',
                105,
                'Failed to get messageId (result empty) for recipient +380970000000, '
                . 'response status REQUIRED_AUTH (105)',
            ],
            'scalar result entry' => [
                '{
                    "response_code": 300,
                    "response_status": "INVALID_REQUEST",
                    "response_result": ["unexpected"]
                }',
                300,
                'Failed to get messageId (unexpected result type string) for recipient +380970000000, '
                . 'response status INVALID_REQUEST (300)',
            ],
        ];
    }

    /**
     * @dataProvider sendFailureDataProvider
     */
    public function testSendFailure(string $body, int $expectedCode, string $expectedMessage): void
    {
        $this->expectException(Delivery\Exception::class);
        $this->expectExceptionCode($expectedCode);
        $this->expectExceptionMessage($expectedMessage);

        $this->send($body);
    }

    public function testBatchReportsRejectedRecipientAsFailed(): void
    {
        $config = new TurboSms\Config('httpToken', 'TestSender');
        $client = $this->createMock(GuzzleHttp\Client::class);
        // batch() derives request keys from time(), so echo back whatever key it actually sent.
        $client->method('request')->willReturnCallback(
            function (string $method, string $uri, array $options): GuzzleHttp\Psr7\Response {
                $key = array_key_first($options[GuzzleHttp\RequestOptions::JSON]);

                return new GuzzleHttp\Psr7\Response(200, [], json_encode([
                    'response_code' => 0,
                    'response_status' => 'OK',
                    'response_result' => [
                        $key => [
                            'response_code' => 802,
                            'response_status' => 'SUCCESS_MESSAGE_PARTIAL_ACCEPTED',
                            'response_result' => [
                                [
                                    'phone' => '+380970000001',
                                    'response_code' => 406,
                                    'message_id' => null,
                                    'response_status' => 'NOT_ALLOWED_RECIPIENT_COUNTRY',
                                ],
                            ],
                        ],
                    ],
                ], JSON_THROW_ON_ERROR));
            }
        );

        $service = new TurboSms\Service($client, $config);
        $results = iterator_to_array($service->batch([
            new Delivery\Message('Test message', '+380970000001'),
        ]));

        $this->assertCount(1, $results);
        $result = reset($results);
        $this->assertSame(Delivery\Result\Status::Failed, $result->status());
        $this->assertSame('NOT_ALLOWED_RECIPIENT_COUNTRY', $result->reason());
    }

    private function send(string $body): Delivery\ResultInterface
    {
        $client = $this->createMock(GuzzleHttp\Client::class);
        $client->method('request')->willReturn(new GuzzleHttp\Psr7\Response(200, [], $body));

        $service = new TurboSms\Service($client, new TurboSms\Config('httpToken', 'TestSender'));

        return $service->send(new Delivery\Message('Test message', '+380970000000'));
    }

    public function viberSenderNameDataProvider(): array
    {
        return [
            'viber with config viber sender' => [
                'configDefaultSender' => 'DefaultSender',
                'configViberSender' => 'ViberSender',
                'messageSender' => null,
                'channel' => 'viber',
                'expectedSenders' => [
                    'viber' => 'ViberSender',
                ],
            ],
            'viber without config viber sender falls back to default' => [
                'configDefaultSender' => 'DefaultSender',
                'configViberSender' => null,
                'messageSender' => null,
                'channel' => 'viber',
                'expectedSenders' => [
                    'viber' => 'DefaultSender',
                ],
            ],
            'viber with message-level override ignores config viber sender' => [
                'configDefaultSender' => 'DefaultSender',
                'configViberSender' => 'ViberSender',
                'messageSender' => 'MessageSender',
                'channel' => 'viber',
                'expectedSenders' => [
                    'viber' => 'MessageSender',
                ],
            ],
            'sms ignores config viber sender' => [
                'configDefaultSender' => 'DefaultSender',
                'configViberSender' => 'ViberSender',
                'messageSender' => null,
                'channel' => 'sms',
                'expectedSenders' => [
                    'sms' => 'DefaultSender',
                ],
            ],
            'multi-channel uses different senders' => [
                'configDefaultSender' => 'DefaultSender',
                'configViberSender' => 'ViberSender',
                'messageSender' => null,
                'channel' => ['sms', 'viber'],
                'expectedSenders' => [
                    'sms' => 'DefaultSender',
                    'viber' => 'ViberSender',
                ],
            ],
            'multi-channel with message override uses same sender for both' => [
                'configDefaultSender' => 'DefaultSender',
                'configViberSender' => 'ViberSender',
                'messageSender' => 'MessageSender',
                'channel' => ['sms', 'viber'],
                'expectedSenders' => [
                    'sms' => 'MessageSender',
                    'viber' => 'MessageSender',
                ],
            ],
        ];
    }

    /**
     * @dataProvider viberSenderNameDataProvider
     */
    public function testViberSenderNamePriority(
        string $configDefaultSender,
        ?string $configViberSender,
        ?string $messageSender,
        string|array $channel,
        array $expectedSenders
    ): void {
        $config = new TurboSms\Config('httpToken', $configDefaultSender, $configViberSender);

        $client = $this->createMock(GuzzleHttp\Client::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->stringContains('message/send.json'),
                $this->callback(function ($options) use ($expectedSenders) {
                    $this->assertArrayHasKey(GuzzleHttp\RequestOptions::JSON, $options);
                    $actualRequest = $options[GuzzleHttp\RequestOptions::JSON];

                    foreach ($expectedSenders as $channelName => $expectedSender) {
                        $this->assertArrayHasKey($channelName, $actualRequest);
                        $this->assertEquals(
                            $expectedSender,
                            $actualRequest[$channelName]['sender'],
                            "Sender for $channelName channel should be $expectedSender"
                        );
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

        $messageOptions = ['channel' => $channel];
        if ($messageSender !== null) {
            $messageOptions['senderName'] = $messageSender;
        }

        $message = new Delivery\Message(
            'Test message',
            '+380970000000',
            $messageOptions
        );

        $result = $service->send($message);
        $this->assertInstanceOf(Delivery\ResultInterface::class, $result);
    }
}
