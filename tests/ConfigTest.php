<?php

namespace Wearesho\Delivery\TurboSms\Tests;

use PHPUnit\Framework\TestCase;
use Wearesho\Delivery\TurboSms;

/**
 * Class ConfigTest
 * @package Wearesho\Delivery\TurboSms\Tests\Unit
 */
class ConfigTest extends TestCase
{
    protected const LOGIN = 'login';
    protected const PASSWORD = 'password';
    protected const SENDER = 'Sender';

    /** @var TurboSms\Config */
    protected $fakeConfig;

    protected function setUp(): void
    {
        $this->fakeConfig = new TurboSms\Config(static::LOGIN, static::PASSWORD, static::SENDER);
    }

    public function testGetLogin(): void
    {
        $this->assertEquals(static::LOGIN, $this->fakeConfig->getLogin());
    }

    public function testGetPassword(): void
    {
        $this->assertEquals(static::PASSWORD, $this->fakeConfig->getPassword());
    }

    public function testGetSenderName(): void
    {
        $this->assertEquals(static::SENDER, $this->fakeConfig->getSenderName());
    }
}
