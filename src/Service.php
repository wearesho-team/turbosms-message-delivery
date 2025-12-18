<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms;

use GuzzleHttp;
use Wearesho\Delivery;

class Service implements Delivery\Batch\ServiceInterface
{
    public const NAME = 'turbosms';

    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_VIBER = 'viber';

    private const BASE_URL = 'https://api.turbosms.ua/';

    private const ENDPOINT_USER_BALANCE = 'user/balance.json';
    private const ENDPOINT_MESSAGE_SEND = 'message/send.json';
    private const ENDPOINT_MESSAGE_SEND_MULTI = 'message/sendmulti.json';

    private static array $availableChannels = [
        self::CHANNEL_SMS,
        self::CHANNEL_VIBER,
    ];

    public function __construct(
        private readonly GuzzleHttp\ClientInterface $client,
        private readonly ConfigInterface $config
    ) {
    }

    public static function instance(): self
    {
        static $instance;
        if (!$instance) {
            $instance = new self(new GuzzleHttp\Client(), new EnvironmentConfig());
        }
        return $instance;
    }

    public function name(): string
    {
        return static::NAME;
    }

    /**
     * @throws Delivery\Exception
     */
    public function balance(): Delivery\BalanceInterface
    {
        $response = BalanceResponse::parse(
            $this->request(self::ENDPOINT_USER_BALANCE)
        );
        if (!$response->isSuccess()) {
            throw new Exception($response->status, $response->code);
        }
        return $response;
    }

    public function batch(iterable $messages): iterable
    {
        $messagesArray = [];
        $requests = [];
        $i = 0;
        $time = time();
        foreach ($messages as $message) {
            $key = "i_" . $time . '_' . $i++;
            $messagesArray[$key] = $message;
            $requests[$key] = $this->getRequestBody($message);
        }

        $response = Response::parse(
            $this->request(self::ENDPOINT_MESSAGE_SEND_MULTI, $requests)
        );

        if (empty($response->result)) {
            throw new Delivery\Exception(
                "Failed to get result for multi request with status " . $response->status,
                $response->code
            );
        }

        if (!$response->isSuccess()) {
            throw new Exception($response->status, $response->code);
        }

        foreach ($response->result as $resultKey => $resultItem) {
            $responseItem = Response::fromArray($resultItem);
            if (empty($responseItem->result)) {
                throw new Exception($responseItem->status, $responseItem->code);
            }
            $responseResultItem = $responseItem->result[array_key_first($responseItem->result)];
            $messageId = $responseResultItem['message_id'] ?? $resultKey;
            $reason = $responseResultItem['response_status'] ?? null;
            yield new Delivery\Result(
                messageId: $messageId,
                message: $messagesArray[$resultKey],
                status: $responseItem->isSuccess() ? Delivery\Result\Status::Accepted : Delivery\Result\Status::Failed,
                reason: $reason,
            );
        }
    }

    public function send(Delivery\MessageInterface $message): Delivery\ResultInterface
    {
        $request = $this->getRequestBody($message);
        $response = Response::parse(
            $this->request(self::ENDPOINT_MESSAGE_SEND, $request)
        );

        if (empty($response->result)) {
            throw new Delivery\Exception(
                "Failed to get messageId (result empty) with status " . $response->status,
                $response->code
            );
        }

        $result = $response->result[array_key_first($response->result)];
        if (!array_key_exists('message_id', $result)) {
            throw new Delivery\Exception(
                "Failed to get messageId (key not found) with status " . $response->status,
                $response->code
            );
        }
        if ($response->isSuccess()) {
            return new Delivery\Result(
                messageId: $result['message_id'],
                message: $message,
                status: Delivery\Result\Status::Accepted,
                reason: $result['response_status'],
            );
        }
        return new Delivery\Result(
            messageId: $result['message_id'],
            message: $message,
            status: Delivery\Result\Status::Failed,
            reason: $result['response_status'],
        );
    }

    protected function getRequestBody(Delivery\MessageInterface $message): array
    {
        $senderName = $this->config->getSenderName();
        $channels = [static::CHANNEL_SMS];

        $messageSenderName = Delivery\Options::get($message, Delivery\Options::SENDER_NAME);
        if (!empty($messageSenderName)) {
            $senderName = $messageSenderName;
        }
        $messageChannel = Delivery\Options::get($message, Delivery\Options::CHANNEL);
        if (!empty($messageChannel)) {
            $channels = $this->validateChannelName($messageChannel);
        }
        $requestMessage = [
            'sender' => $senderName,
            'text' => $message->getText(),
        ];
        $request = [
            'recipients' => [
                $message->getRecipient(),
            ],
        ];
        foreach ($channels as $channel) {
            $request[$channel] = $requestMessage;
        }
        if (in_array(self::CHANNEL_VIBER, $channels)) {
            $viberOptions = $this->mapViberRequestOptions($message);
            if (!empty($viberOptions)) {
                $request[self::CHANNEL_VIBER] = array_merge($request[self::CHANNEL_VIBER], $viberOptions);
            }
        }

        return $request;
    }

    protected function request(string $endpoint, ?array $data = null): string
    {
        $requestOptions = [
            GuzzleHttp\RequestOptions::HEADERS => [
                'Authorization' => 'Basic ' . $this->config->getHttpToken(),
            ]
        ];
        if (!empty($data)) {
            $requestOptions[GuzzleHttp\RequestOptions::JSON] = $data;
        }

        try {
            $response = $this->client->request(
                'POST',
                static::BASE_URL . rtrim($endpoint, '/'),
                $requestOptions
            );
        } catch (GuzzleHttp\Exception\GuzzleException $e) {
            throw new Delivery\Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $response->getBody()->__toString();
    }

    protected function validateChannelName(string|array $value): array
    {
        foreach ((array)$value as $channelName) {
            if (!in_array($channelName, self::$availableChannels)) {
                throw new Delivery\Exception("Unsupported channel: " . $channelName);
            }
        }

        return (array)$value;
    }

    protected function mapViberRequestOptions(Delivery\MessageInterface $message): array
    {
        $options = [];
        foreach (ViberOptions::cases() as $viberOption) {
            $optionValue = Delivery\Options::get($message, $viberOption->value);
            if (empty($optionValue)) {
                continue;
            }
            $options[$viberOption->getRequestParameter()] = $optionValue;
        }
        return $options;
    }
}
