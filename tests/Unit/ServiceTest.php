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

    /** @var Delivery\TurboSms\Service */
    protected $service;

    protected function setUp(): void
    {
        $this->service = new Delivery\TurboSms\Service(
            new Delivery\TurboSms\Config(static::LOGIN, static::PASSWORD),
            new GuzzleHttp\Client()
        );
    }

    public function testGetConfig(): void
    {
        $this->assertEquals(
            new Delivery\TurboSms\Config(static::LOGIN, static::PASSWORD),
            $this->service->getConfig()
        );
    }

    public function testFailed(): void
    {
        $this->expectException(Delivery\Exception::class);
        $this->expectExceptionMessage('Неверный логин или пароль');

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->service->send(new Delivery\Message('text', '+380000000000'));
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
        $this->service->send(new Delivery\MessageWithSender('text', '+380000000000', 'Too many characters'));
    }

    public function testFailedGetBalance(): void
    {
        $this->expectException(Delivery\Exception::class);
        $this->expectExceptionMessage('Неверный логин или пароль');

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->service->balance();
    }
}
