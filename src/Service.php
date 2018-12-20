<?php

namespace Wearesho\Delivery\TurboSms;

use GuzzleHttp\ClientInterface;
use Meng\AsyncSoap;
use Wearesho\Delivery;

/**
 * Class Service
 * @package Wearesho\Delivery\TurboSms
 */
class Service implements Delivery\ServiceInterface, Delivery\CheckBalance
{
    protected const AUTH_SUCCESS = "Вы успешно авторизировались";
    protected const SEND_SUCCESS = "Сообщения успешно отправлены";
    protected const SENDER_NAME_MAX_LENGTH = 11;

    /** @var ConfigInterface */
    protected $config;

    /** @var AsyncSoap\SoapClientInterface */
    protected $client;

    public function __construct(ConfigInterface $config, ClientInterface $client)
    {
        $this->config = $config;
        $this->client = (new AsyncSoap\Guzzle\Factory())->create($client, $config->getUri());
    }

    /**
     * @param Delivery\MessageInterface $message
     *
     * @throws Delivery\Exception
     */
    public function send(Delivery\MessageInterface $message): void
    {
        $this->validateRecipient($message);
        $sender = $this->fetchSenderName($message);
        $this->auth();

        $sms = [
            'sender' => $sender,
            'destination' => $message->getRecipient(),
            'text' => $message->getText(),
        ];

        $response = $this->client->call('SendSMS', [$sms]);

        $status = $response->SendSMSResult->ResultArray;
        if (is_array($status) && !preg_match("/" . static::SEND_SUCCESS . "/", $status[0])) {
            throw new Delivery\Exception($status[0]);
        }

        if (!is_array($status)) {
            throw new Delivery\Exception($status);
        }
    }

    /**
     * @return Delivery\BalanceInterface
     * @throws Delivery\Exception
     */
    public function balance(): Delivery\BalanceInterface
    {
        $this->auth();

        $response = $this->client->call('GetCreditBalance', [])->GetCreditBalanceResult;

        if (!is_numeric($response)) {
            throw new Delivery\Exception($response);
        }

        return new Delivery\Balance((float)$response, 'Credits');
    }

    /**
     * @throws Delivery\Exception
     */
    public function auth(): void
    {
        $credentials = [
            'login' => $this->config->getLogin(),
            'password' => $this->config->getPassword(),
        ];

        $result = $this->client->call('Auth', [$credentials]);

        if (trim($result->AuthResult) !== static::AUTH_SUCCESS) {
            throw new Delivery\Exception($result->AuthResult);
        }
    }

    /**
     * @param Delivery\MessageInterface $message
     *
     * @throws Delivery\Exception
     */
    protected function validateRecipient(Delivery\MessageInterface $message): void
    {
        if (!preg_match('/^\+380\d{9}$/', $message->getRecipient())) {
            throw new Delivery\Exception("Unsupported recipient format");
        }
    }

    /**
     * @param Delivery\MessageInterface $message
     *
     * @return string
     * @throws Delivery\Exception
     */
    protected function fetchSenderName(Delivery\MessageInterface $message): string
    {
        $name = $message instanceof Delivery\ContainsSenderName
            ? $message->getSenderName()
            : $this->config->getSenderName();

        if (mb_strlen($name) > static::SENDER_NAME_MAX_LENGTH) {
            throw new Delivery\Exception('Sender name must be equal or less than 11 symbols');
        }

        return $name;
    }
}
