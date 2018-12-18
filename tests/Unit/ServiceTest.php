<?php

namespace Wearesho\Delivery\TurboSms\Tests\Unit;

use GuzzleHttp;
use PHPUnit\Framework\TestCase;
use Wearesho\Delivery\Message;
use Wearesho\Delivery\TurboSms;

/**
 * Class ServiceTest
 * @package Wearesho\Delivery\TurboSms\Tests\Unit
 */
class ServiceTest extends TestCase
{
    protected const LOGIN = 'login';
    protected const PASSWORD = 'password';

    /** @var TurboSms\Service */
    protected $fakeService;
    
    protected function setUp(): void
    {
        $this->fakeService = new TurboSms\Service(
            new TurboSms\Config(static::LOGIN, static::PASSWORD),
            new GuzzleHttp\Client()
        );
    }

    public function testTest(): void
    {
        $this->fakeService->send(new Message('text', 'recipient'));
    }
}
