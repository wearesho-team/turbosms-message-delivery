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
    protected $soap;

    /** @var ClientInterface */
    private $client;

    /**
     * Service constructor.
     *
     * @param ConfigInterface $config
     * @param ClientInterface $client
     *
     * @throws CookiesDisabledException
     */
    public function __construct(ConfigInterface $config, ClientInterface $client)
    {
        if (!$client->getConfig('cookies')) {
            throw new CookiesDisabledException($client, "Parameter 'cookies' must be enabled for guzzle client");
        }

        $this->config = $config;
        $this->client = $client;
        $this->soap = (new AsyncSoap\Guzzle\Factory())->create($client, $config->getUri());
    }

    /**
     * @param Delivery\MessageInterface $message
     *
     * @throws Delivery\Exception
     */
    public function send(Delivery\MessageInterface $message): void
    {
        $recipient = $this->formatRecipient($message->getRecipient());
        $this->validateRecipient($recipient);

        $sender = $this->fetchSenderName($message);
        $this->auth();

        $sms = [
            'sender' => $sender,
            'destination' => $recipient,
            'text' => $message->getText(),
        ];

        $response = $this->soap->call('SendSMS', [$sms]);

        $status = $response->SendSMSResult->ResultArray;
        if (\is_array($status) && !\preg_match("/" . static::SEND_SUCCESS . "/", $status[0])) {
            throw new Delivery\Exception($status[0]);
        }

        if (!\is_array($status)) {
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

        $response = $this->soap->call('GetCreditBalance', [])->GetCreditBalanceResult;

        if (!\is_numeric($response)) {
            throw new Delivery\Exception($response);
        }

        return new Delivery\Balance((float)$response, 'Credits');
    }

    /**
     * @throws Delivery\Exception
     */
    public function auth(): void
    {
        $cookieSession = $this->client->getConfig('cookies');

        if ($cookieSession && $cookieSession->getCookieByName('PHPSESSID')) {
            return;
        }

        $credentials = [
            'login' => $this->config->getLogin(),
            'password' => $this->config->getPassword(),
        ];

        $result = $this->soap->call('Auth', [$credentials]);

        if (\trim($result->AuthResult) !== static::AUTH_SUCCESS) {
            throw new Delivery\Exception($result->AuthResult);
        }
    }

    protected function formatRecipient(string $recipient): string
    {
        if ($recipient[0] !== '+') {
            $recipient = "+{$recipient}";
        }

        return $recipient;
    }

    /**
     * @param string $recipient
     *
     * @throws Delivery\Exception
     */
    protected function validateRecipient(string $recipient): void
    {
        if (!\preg_match('/^\+380\d{9}$/', $recipient)) {
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

        if (\mb_strlen($name) > static::SENDER_NAME_MAX_LENGTH) {
            throw new Delivery\Exception('Sender name must be equal or less than 11 symbols');
        }

        return $name;
    }
}
