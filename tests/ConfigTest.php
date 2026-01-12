<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms\Tests;

use PHPUnit\Framework\TestCase;
use Wearesho\Delivery\TurboSms;

class ConfigTest extends TestCase
{
    protected const HTTP_TOKEN = 'login';
    protected const SENDER = 'Sender';

    protected TurboSms\Config $fakeConfig;

    protected function setUp(): void
    {
        $this->fakeConfig = new TurboSms\Config(static::HTTP_TOKEN, static::SENDER);
    }

    public function testGetHttpToken(): void
    {
        $this->assertEquals(static::HTTP_TOKEN, $this->fakeConfig->getHttpToken());
    }

    public function testGetSenderName(): void
    {
        $this->assertEquals(static::SENDER, $this->fakeConfig->getSenderName());
    }

    public function testGetViberSenderNameWhenSet(): void
    {
        $config = new TurboSms\Config(static::HTTP_TOKEN, static::SENDER, 'ViberSender');
        $this->assertEquals('ViberSender', $config->getViberSenderName());
    }

    public function testGetViberSenderNameWhenNull(): void
    {
        $config = new TurboSms\Config(static::HTTP_TOKEN, static::SENDER);
        $this->assertNull($config->getViberSenderName());
    }

    public function testGetViberSenderNameExplicitNull(): void
    {
        $config = new TurboSms\Config(static::HTTP_TOKEN, static::SENDER, null);
        $this->assertNull($config->getViberSenderName());
    }
}
