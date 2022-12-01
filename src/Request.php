<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms;

use GuzzleHttp;

class Request extends GuzzleHttp\Psr7\Request
{
    public function __construct(string $action, array $config)
    {
        parent::__construct('POST', 'http://turbosms.in.ua/api/soap.html', [
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => 'http://turbosms.in.ua/api/Turbo/' . $action,
        ], $this->formatBody($action, $config));
        $this->formatBody($action, $config);
    }

    private function formatBody(string $action, array $config): string
    {
        array_walk(
            $config,
            fn(&$item, $key) => $item =
                "<ns1:{$key}>" . htmlentities($item, ENT_XML1) . "</ns1:{$key}>"
        );
        $body = implode("", $config);
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ns1=\"http://turbosms.in.ua/api/Turbo\"><SOAP-ENV:Body><ns1:{$action}>{$body}</ns1:{$action}></SOAP-ENV:Body></SOAP-ENV:Envelope>";// phpcs:ignore
    }
}
