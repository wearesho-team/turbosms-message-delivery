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
}
