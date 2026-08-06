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
            $responseResultItem = is_array($responseResultItem) ? $responseResultItem : [];
            $messageId = $responseResultItem['message_id'] ?? null;
            $resultCode = $responseResultItem['response_code'] ?? null;
            $reason = is_string($responseResultItem['response_status'] ?? null)
                ? $responseResultItem['response_status']
                : null;
            yield new Delivery\Result(
                // Falling back to the request key keeps the batch iterable, unlike send() which throws.
                messageId: is_string($messageId) && $messageId !== '' ? $messageId : (string)$resultKey,
                message: $messagesArray[$resultKey],
                status: $this->resolveStatus($responseItem, $resultCode),
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
                "Failed to get messageId (result empty) for recipient " . $message->getRecipient()
                . ", " . $this->describeResponse($response),
                $response->code
            );
        }

        $result = $response->result[array_key_first($response->result)];
        if (!is_array($result)) {
            throw new Delivery\Exception(
                "Failed to get messageId (unexpected result type " . get_debug_type($result) . ")"
                . " for recipient " . $message->getRecipient()
                . ", " . $this->describeResponse($response),
                $response->code
            );
        }

        // TurboSMS reports per-recipient failures inside a request that may itself be reported as
        // successful (802/803 partial). Such entries carry message_id: null.
        $messageId = $result['message_id'] ?? null;
        $resultCode = $result['response_code'] ?? null;
        $resultStatus = is_string($result['response_status'] ?? null) ? $result['response_status'] : null;

        if (!is_string($messageId) || $messageId === '') {
            $recipient = is_string($result['phone'] ?? null) ? $result['phone'] : $message->getRecipient();
            throw new Delivery\Exception(
                "Failed to get messageId for recipient " . $recipient
                . ": " . ($resultStatus ?? 'unknown status')
                . " (" . (is_int($resultCode) ? $resultCode : 'no code') . ")"
                . ", " . $this->describeResponse($response),
                is_int($resultCode) ? $resultCode : $response->code
            );
        }

        return new Delivery\Result(
            messageId: $messageId,
            message: $message,
            status: $this->resolveStatus($response, $resultCode),
            reason: $resultStatus,
        );
    }

    /**
     * A message counts as accepted only when the request as a whole and the individual recipient
     * both succeeded — a partial success (802/803) hides per-recipient failures. This matters for
     * batch(), which yields rejected recipients instead of throwing. In send() a documented
     * rejection carries message_id: null and is rejected before reaching here, so the
     * per-recipient check only guards undocumented payloads.
     */
    protected function resolveStatus(Response $response, mixed $resultCode): Delivery\Result\Status
    {
        $isAccepted = $response->isSuccess()
            && (!is_int($resultCode) || Response::isSuccessCode($resultCode));

        return $isAccepted ? Delivery\Result\Status::Accepted : Delivery\Result\Status::Failed;
    }

    protected function describeResponse(Response $response): string
    {
        return "response status " . $response->status . " (" . $response->code . ")";
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
            // Apply Viber-specific sender name
            if (empty($messageSenderName)) {
                $viberSenderName = $this->config->getViberSenderName();
                if (!empty($viberSenderName)) {
                    $request[self::CHANNEL_VIBER]['sender'] = $viberSenderName;
                }
            }

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
