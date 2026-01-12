<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms\Tests;

use Horat1us\Environment\Exception;
use PHPUnit\Framework\TestCase;
use Wearesho\Delivery\TurboSms;

class EnvironmentConfigTest extends TestCase
{
    protected const HTTP_TOKEN = 'httpToken';
    protected const SENDER = 'sender';

    protected TurboSms\EnvironmentConfig $fakeConfig;

    protected function setUp(): void
    {
        $this->fakeConfig = new TurboSms\EnvironmentConfig();
    }

    public function testSuccessGetPassword(): void
    {
        putenv('TURBOSMS_HTTP_TOKEN=' . static::HTTP_TOKEN);

        $this->assertEquals(static::HTTP_TOKEN, $this->fakeConfig->getHttpToken());
    }

    public function testFailedGetPassword(): void
    {
        $this->expectException(Exception\Missing::class);

        putenv('TURBOSMS_HTTP_TOKEN');

        $this->fakeConfig->getHttpToken();
    }

    public function testSuccessGetSenderName(): void
    {
        putenv('TURBOSMS_SENDER=' . static::SENDER);

        $this->assertEquals(static::SENDER, $this->fakeConfig->getSenderName());
    }

    public function testSuccessGetDefaultSenderName(): void
    {
        putenv('TURBOSMS_SENDER');

        $this->assertEquals(TurboSms\ConfigInterface::SENDER, $this->fakeConfig->getSenderName());
    }

    public function testGetViberSenderNameWhenSet(): void
    {
        putenv('TURBOSMS_VIBER_SENDER=ViberSender');

        $this->assertEquals('ViberSender', $this->fakeConfig->getViberSenderName());
    }

    public function testGetViberSenderNameWhenNotSet(): void
    {
        putenv('TURBOSMS_VIBER_SENDER');

        $this->assertNull($this->fakeConfig->getViberSenderName());
    }

    public function testGetViberSenderNameDoesNotUseDefaultSender(): void
    {
        putenv('TURBOSMS_SENDER=DefaultSender');
        putenv('TURBOSMS_VIBER_SENDER');

        // Viber sender should be null even if default sender is set
        $this->assertNull($this->fakeConfig->getViberSenderName());
        $this->assertEquals('DefaultSender', $this->fakeConfig->getSenderName());
    }
}
