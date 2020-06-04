<?php

namespace Wearesho\Delivery\TurboSms\Tests;

use Horat1us\Environment\Exception;
use PHPUnit\Framework\TestCase;
use Wearesho\Delivery\TurboSms;

/**
 * Class EnvironmentConfigTest
 * @package Wearesho\Delivery\TurboSms\Tests\Unit
 */
class EnvironmentConfigTest extends TestCase
{
    protected const LOGIN = 'login';
    protected const PASSWORD = 'password';
    protected const URI = 'uri';
    protected const SENDER = 'sender';

    /** @var TurboSms\EnvironmentConfig */
    protected $fakeConfig;

    protected function setUp(): void
    {
        $this->fakeConfig = new TurboSms\EnvironmentConfig();
    }

    public function testSuccessGetLogin(): void
    {
        putenv('TURBOSMS_LOGIN=' . static::LOGIN);

        $this->assertEquals(static::LOGIN, $this->fakeConfig->getLogin());
    }

    public function testFailedGetLogin(): void
    {
        $this->expectException(Exception\Missing::class);

        putenv('TURBOSMS_LOGIN');

        $this->fakeConfig->getLogin();
    }

    public function testSuccessGetPassword(): void
    {
        putenv('TURBOSMS_PASSWORD=' . static::PASSWORD);

        $this->assertEquals(static::PASSWORD, $this->fakeConfig->getPassword());
    }

    public function testFailedGetPassword(): void
    {
        $this->expectException(Exception\Missing::class);

        putenv('TURBOSMS_PASSWORD');

        $this->fakeConfig->getPassword();
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
}
