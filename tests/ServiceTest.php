<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms\Tests;

use GuzzleHttp;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Wearesho\Delivery\Balance;
use Wearesho\Delivery\Message\Batch;
use Wearesho\Delivery\TurboSms;
use Wearesho\Delivery\Message;

class ServiceTest extends TestCase
{
    public function testAuth(): void
    {
        $config = new TurboSms\Config("test", "password");
        $guzzle = $this->getMockForAbstractClass(GuzzleHttp\ClientInterface::class);
        $method = $guzzle->expects($this->exactly(2))->method('send');
        $method->willReturnCallback(function ($request) {
            $this->assertEquals(
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ns1=\"http://turbosms.in.ua/api/Turbo\"><SOAP-ENV:Body><ns1:Auth><ns1:login>test</ns1:login><ns1:password>password</ns1:password></ns1:Auth></SOAP-ENV:Body></SOAP-ENV:Envelope>",// phpcs:ignore
                (string)$request->getBody()
            );
            return new GuzzleHttp\Psr7\Response(200, [
                'Set-Cookie' => 'PHPSESSID=RANDOMCOOKIE; path=/',
                'Content-Type' => 'text/xml'
            ], "<?xml version=\"1.0\"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ns1=\"http://turbosms.in.ua/api/Turbo\"><SOAP-ENV:Body><ns1:Auth><ns1:AuthResult>Вы успешно авторизировались</ns1:AuthResult></ns1:Auth></SOAP-ENV:Body></SOAP-ENV:Envelope>"); // phpcs:ignore
        });
        $client = new TurboSms\Service($guzzle, $config);
        $client->auth();

        $method->willReturnCallback(function (RequestInterface $request) {
            $this->assertEquals(
                "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ns1=\"http://turbosms.in.ua/api/Turbo\"><SOAP-ENV:Body><ns1:GetCreditBalance></ns1:GetCreditBalance></SOAP-ENV:Body></SOAP-ENV:Envelope>", // phpcs:ignore
                (string)$request->getBody()
            );
            $this->assertContains('PHPSESSID=RANDOMCOOKIE', (array)$request->getHeader('Cookie'));
            // phpcs:ignore
            return new GuzzleHttp\Psr7\Response(200, [], "<SOAP-ENV:Body><ns1:GetCreditBalance><ns1:GetCreditBalanceResult>1337</ns1:GetCreditBalanceResult></ns1:GetCreditBalance></SOAP-ENV:Body>");
        });
        $this->assertEquals(new Balance(1337.0, 'UAH'), $client->balance());
    }

    public function testSendBatch(): void
    {
        $client = $this->createPartialMock(TurboSms\Service::class, ['batch']);
        $client->expects($this->once())->method('batch')
            ->with('Text', '380000000000', '380000000001', '380000000002');
        $batch = Batch::create('Text', ...array_map(
            fn($i) => '38000000000' . $i,
            range(0, 2)
        ));
        $client->send($batch);
        $this->assertCount(3, $batch->history());
    }

    public function testSendSingle(): void
    {
        $client = $this->createPartialMock(TurboSms\Service::class, ['batch']);
        $client->expects($this->once())->method('batch')
            ->with('Single', '380123456789');
        $client->send(new Message('Single', '380123456789'));
    }
}
