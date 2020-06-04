<?php

namespace Wearesho\Delivery\TurboSms;

use GuzzleHttp;
use Wearesho\Delivery;

class Service implements Delivery\ServiceInterface, Delivery\CheckBalance
{
    private ConfigInterface $config;
    private GuzzleHttp\ClientInterface $client;

    private GuzzleHttp\Cookie\CookieJar $cookies;

    public function __construct(GuzzleHttp\ClientInterface $client, ConfigInterface $config)
    {
        $this->client = $client;
        $this->config = $config;

        $this->cookies = new GuzzleHttp\Cookie\CookieJar;
    }

    public static function instance(): self
    {
        static $instance;
        if (!$instance) {
            $instance = new self(new GuzzleHttp\Client, new EnvironmentConfig);
        }
        return $instance;
    }

    protected function request(string $action, array $config = [])
    {
        if ($this->cookies->count() === 0 && $action !== 'Auth') {
            $this->auth();
        }
        $request = $this->cookies->withCookieHeader(new Request($action, $config));
        $response = $this->client->send($request);
        $this->cookies->extractCookies($request, $response);
        $body = (string)$response->getBody();
        if (!preg_match(
            '/<SOAP-ENV:Body>(?:<ns1:\w+>){2}(.+)(?:<\/ns1:\w+>){2}<\/SOAP-ENV:Body>/m',
            $body,
            $matches
        )) {
            return $body;
        }
        return $matches[1];
    }

    public function auth(): string
    {
        $response = $this->request(
            'Auth',
            ['login' => $this->config->getLogin(), 'password' => $this->config->getPassword()]
        );
        if ($response !== 'Вы успешно авторизировались') {
            throw new Delivery\Exception($response);
        }
        return $response;
    }

    public function balance(): Delivery\BalanceInterface
    {
        $response = $this->request('GetCreditBalance');
        if (!is_numeric($response)) {
            throw new Delivery\Exception($response);
        }
        return new Delivery\Balance(floatval($response), 'UAH');
    }

    public function batch(string $text, string $recipient, ...$recipients): array
    {
        $destination = implode(',', array_map(
            fn(string $recipient) => preg_replace(
                '/^\+?(?:38)?0(\d{9})$/',
                '+380$1',
                $recipient
            ),
            [$recipient, ...$recipients]
        ));
        $sender = $this->config->getSenderName();

        $response = $this->request('SendSMS', compact('destination', 'text', 'sender'));
        if (!preg_match_all('/<(?:[a-z0-9]+):ResultArray>(.+)<\/(?:[a-z0-9]+):ResultArray>/U', $response, $matches)) {
            throw new Delivery\Exception($response);
        }

        $status = array_shift($matches[1]);
        if (!str_contains($status, 'Не удалось отправить сообщение на некоторые номера')
            && !str_contains($status, 'Сообщения успешно отправлены')) {
            throw new Delivery\Exception($status);
        }

        return $matches[1];
    }

    public function send(Delivery\MessageInterface $message): void
    {
        if (!$message instanceof Delivery\Message\BatchInterface) {
            $this->batch($message->getText(), $message->getRecipient());
            return;
        }
        while ($message->valid()) {
            $batch[$message->getText()][] = $message->getRecipient();
            $message->next();
        }
        $message->rewind();
        foreach ($batch as $text => $recipients) {
            $this->batch($text, ...$recipients);
            foreach ($recipients as $recipient) {
                $message->next(
                    new Delivery\HistoryItem(
                        new Delivery\Message($text, $recipient),
                        static::class,
                        true
                    )
                );
            }
        }
    }
}
