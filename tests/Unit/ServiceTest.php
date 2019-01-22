<?php

namespace Wearesho\Delivery\TurboSms\Tests\Unit;

use GuzzleHttp;
use PHPUnit\Framework\TestCase;
use Wearesho\Delivery;

/**
 * Class ServiceTest
 * @package Wearesho\Delivery\TurboSms\Tests\Unit
 */
class ServiceTest extends TestCase
{
    protected const LOGIN = 'login';
    protected const PASSWORD = 'password';
    protected const VALID_RECIPIENT = '+380000000000';
    protected const MESSAGE = 'text';

    /** @var Delivery\TurboSms\Service */
    protected $service;

    /** @var Delivery\TurboSms\Config */
    protected $config;

    /** @var GuzzleHttp\Handler\MockHandler */
    protected $mock;

    /** @var array */
    protected $container;

    protected function setUp(): void
    {
        $this->mock = new GuzzleHttp\Handler\MockHandler();
        $this->container = [];
        $history = GuzzleHttp\Middleware::history($this->container);

        $stack = new GuzzleHttp\HandlerStack($this->mock);
        $stack->push($history);

        $this->mock->append(
            new GuzzleHttp\Psr7\Response(
                200,
                ['content-type' => 'text/html'],
                file_get_contents(dirname(__DIR__) . '/Mock/wsdl.xml')
            )
        );

        $this->service = new Delivery\TurboSms\Service(
            new Delivery\TurboSms\Config(static::LOGIN, static::PASSWORD),
            new GuzzleHttp\Client([
                'cookies' => true,
                'handler' => $stack,
            ])
        );
    }

    public function testSuccessAuth(): void
    {
        $response = $this->mockResponse('SuccessAuthResponse');
        $this->mock->append($response);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->service->auth();

        $this->assertSame($response, $this->container[1]['response']);
    }

    /**
     * @dataProvider providerAuthErrorsResponse
     */
    public function testFailedAuth(GuzzleHttp\Psr7\Response $response, string $message): void
    {
        $this->mock->append($response);

        $this->expectException(Delivery\Exception::class);
        $this->expectExceptionMessage($message);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->service->auth();

        $this->assertSame($response, $this->container[1]['response']);
    }

    public function providerAuthErrorsResponse(): array
    {
        return [
            [$this->mockResponse('NotEnoughArgumentsAuth'), 'Не достаточно параметров для выполнения функции',],
            [$this->mockResponse('WrongCredentialsAuth'), 'Неверный логин или пароль',],
            [
                $this->mockResponse('NotActivatedAccountAuth'),
                'Ваша учётная запись не активирована, свяжитесь с администрацией',
            ],
            [
                $this->mockResponse('BlockedAccountAuth'),
                'Ваша учётная запись заблокирована за нарушения, свяжитесь с администрацией',
            ],
            [
                $this->mockResponse('DisabledAccountAuth'),
                'Ваша учётная запись отключена, свяжитесь с администрацией',
            ]
        ];
    }

    public function testInvalidRecipientFormat(): void
    {
        $this->expectException(Delivery\Exception::class);
        $this->expectExceptionMessage('Unsupported recipient format');

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->service->send(new Delivery\Message('text', 'invalid format'));
    }

    public function testInvalidLengthSenderName(): void
    {
        $this->expectException(Delivery\Exception::class);
        $this->expectExceptionMessage('Sender name must be equal or less than 11 symbols');

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->service->send(new Delivery\MessageWithSender(
            static::MESSAGE,
            static::VALID_RECIPIENT,
            'Too many characters'
        ));
    }

    public function testSuccessGetBalance(): void
    {
        $this->mockAuth();
        $this->mock->append($this->mockResponse('SuccessGetCreditBalance'));

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertEquals(
            new Delivery\Balance(
                2500.75,
                'Credits'
            ),
            $this->service->balance()
        );
    }

    public function testFailedGetCreditBalance(): void
    {
        $this->mockAuth();
        $this->mock->append($this->mockResponse('FailedGetCreditBalance'));

        $this->expectException(Delivery\Exception::class);
        $this->expectExceptionMessage('Вы не авторизированы'); // Потеряна сессия авторизации

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->service->balance();
    }

    public function testSuccessSendSms(): void
    {
        $this->mockAuth();
        $response = $this->mockSendSmsResponse('Success');
        $this->mock->append($response);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->service->send($this->mockMessage());

        $this->assertSame($response, $this->container[2]['response']);
    }

    /**
     * @dataProvider providerSendSmsFailedData
     */
    public function testFailedSendSms(GuzzleHttp\Psr7\Response $response, string $message): void
    {
        $this->mockAuth();
        $this->mock->append($response);

        $this->expectException(Delivery\Exception::class);
        $this->expectExceptionMessage($message);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->service->send($this->mockMessage());
    }

    public function providerSendSmsFailedData(): array
    {
        return [
            [$this->mockSendSmsResponse('NotEnoughArguments'), 'Не достаточно параметров для выполнения функции',],
            [$this->mockSendSmsResponse('NotAuthorized'), 'Вы не авторизированы',],
            [
                $this->mockSendSmsResponse('InvalidSender'),
                "Неправильная подпись."
                . PHP_EOL
                . "Подпись отправителя не должна быть длиннее 11 символов"
                . " и может содержать буквы латинского алфавита, цифры, а также знаки: .-&",
            ],
            [$this->mockSendSmsResponse('SenderInBlackList'), 'Данная подпись запрещена администратором',]
        ];
    }

    public function testWithDisabledCookies(): void
    {
        $this->expectException(Delivery\TurboSms\CookiesDisabledException::class);

        new Delivery\TurboSms\Service(
            new Delivery\TurboSms\Config('username', 'password'),
            new GuzzleHttp\Client(['cookies' => false])
        );
    }

    protected function mockAuth(): void
    {
        $this->mock->append($this->mockResponse(
            'SuccessAuthResponse',
            [
                'set-cookie' => 'PHPSESSID=q4dq58tn0pmnm8pjso2c927tp2; path=/',
                'connection' => 'keep-alive',
            ]
        ));
    }

    protected function mockSendSmsResponse(string $xmlFileName): GuzzleHttp\Psr7\Response
    {
        return $this->mockResponse("SendSMSResponse/{$xmlFileName}");
    }

    protected function mockResponse(string $xmlFileName, array $headers = []): GuzzleHttp\Psr7\Response
    {
        $body = file_get_contents(dirname(__DIR__) . "/Mock/{$xmlFileName}.xml");

        return new GuzzleHttp\Psr7\Response(
            200,
            array_merge($headers, [
                'content-type' => 'text/html',
                'content-length' => mb_strlen($body),
            ]),
            $body
        );
    }

    protected function mockMessage(): Delivery\Message
    {
        return new Delivery\Message('Test', static::VALID_RECIPIENT);
    }
}
