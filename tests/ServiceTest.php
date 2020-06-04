<?php

namespace Wearesho\Delivery\TurboSms\Tests;

use GuzzleHttp;
use PHPUnit\Framework\TestCase;
use Wearesho\Delivery\Message\Batch;
use Wearesho\Delivery\TurboSms;

class ServiceTest extends TestCase
{
    public function testAuth()
    {
        $config = new TurboSms\Config("test", "password");
        $guzzle = $this->getMockForAbstractClass(GuzzleHttp\ClientInterface::class);
        $method = $guzzle->expects($this->exactly(2))->method('send');
        $method->willReturnCallback(function ($request) {
            $this->assertEquals(
                <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://turbosms.in.ua/api/Turbo"><SOAP-ENV:Body><ns1:Auth><ns1:login>test</ns1:login><ns1:password>password</ns1:password></ns1:Auth></SOAP-ENV:Body></SOAP-ENV:Envelope>
XML,
                (string)$request->getBody()
            );
            return new GuzzleHttp\Psr7\Response(200, [
                'Set-Cookie' => 'PHPSESSID=RANDOMCOOKIE; path=/',
            ]);
        });
        $client = new TurboSms\Service($guzzle, $config);
        $client->auth();

        $method->willReturn(new GuzzleHttp\Psr7\Response);

        $response = $client->GetCreditBalance();
        echo $response;
    }

    public function testSend()
    {
        $client = $this->createPartialMock(TurboSms\Service::class, ['batch']);
        $client->expects($this->once())->method('batch')
            ->with('Text', '380000000000', '380000000001', '380000000002');
        $batch = Batch::create('Text', ...array_map(
            fn($i) => '38000000000' . $i, range(0, 2)));
        $client->send($batch);
        $this->assertCount(3, $batch->history());
    }
}
