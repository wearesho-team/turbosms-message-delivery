<?php

namespace Wearesho\Delivery\TurboSms\Tests\Unit;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Wearesho\Delivery\TurboSms\CookiesDisabledException;

/**
 * Class CookiesDisabledExceptionTest
 * @package Wearesho\Delivery\TurboSms\Tests\Unit
 */
class CookiesDisabledExceptionTest extends TestCase
{
    /** @var CookiesDisabledException */
    protected $exception;

    public function setUp(): void
    {
        $this->exception = new CookiesDisabledException(new Client());
    }

    public function testGetClient(): void
    {
        $this->assertEquals(new Client(), $this->exception->getClient());
    }
}
