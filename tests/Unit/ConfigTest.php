<?php

namespace Wearesho\Delivery\TurboSms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wearesho\Delivery\TurboSms\Config;

/**
 * Class ConfigTest
 * @package Wearesho\Delivery\TurboSms\Tests\Unit
 */
class ConfigTest extends TestCase
{
    protected const LOGIN = 'login';
    protected const PASSWORD = 'password';

    /** @var Config */
    protected $fakeConfig;

    protected function setUp(): void
    {
        $this->fakeConfig = new Config(static::LOGIN, static::PASSWORD);
    }

    public function testGetLogin(): void
    {
        $this->assertEquals(static::LOGIN, $this->fakeConfig->getLogin());
    }

    public function testGetPassword(): void
    {
        $this->assertEquals(static::PASSWORD, $this->fakeConfig->getPassword());
    }
}
