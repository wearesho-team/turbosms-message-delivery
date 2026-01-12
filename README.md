# TurboSMS Integration
[![Test & Lint](https://github.com/wearesho-team/turbosms-message-delivery/actions/workflows/php.yml/badge.svg?branch=master)](https://github.com/wearesho-team/turbosms-message-delivery/actions/workflows/php.yml)
[![Latest Stable Version](https://poser.pugx.org/wearesho-team/turbosms-message-delivery/v/stable.png)](https://packagist.org/packages/wearesho-team/turbosms-message-delivery)
[![Total Downloads](https://poser.pugx.org/wearesho-team/turbosms-message-delivery/downloads.png)](https://packagist.org/packages/wearesho-team/turbosms-message-delivery)
[![codecov](https://codecov.io/gh/wearesho-team/turbosms-message-delivery/branch/master/graph/badge.svg)](https://codecov.io/gh/wearesho-team/turbosms-message-delivery)

[wearesho-team/message-delivery](https://github.com/wearesho-team/message-delivery) implementation of
[Delivery\ServiceInterface](https://github.com/wearesho-team/message-delivery/blob/1.3.4/src/ServiceInterface.php)

## Installation
```bash
composer require wearsho-team/turbosms-message-delivery:^3.0
```

## Quick Start
- Install to your Project
```bash
composer require wearsho-team/turbosms-message-delivery:^3.0
```
- Configure environment

| Variable              | Required | Description                                    |
|-----------------------|----------|------------------------------------------------|
| TURBOSMS_HTTP_TOKEN   | Yes      | HTTP API Token                                 |
| TURBOSMS_SENDER       | no       | Sender name, that was declared in your account |
| TURBOSMS_VIBER_SENDER | no       | Viber-specific sender name (optional)          |

- Use in your code
```php
<?php
use Wearesho\Delivery\Message;
use Wearesho\Delivery\MessageOptionsInterface;
use Wearesho\Delivery\TurboSms;
$service = TurboSms\Service::instance();
$service->auth();
$service->balance();
$service->send(new Message("Text", "3809700000000"));
$service->batch("Text", ["3809700000000", "3809700000001"]);
$service->batch("Text", "3809700000001", [
    MessageOptionsInterface::OPTION_SENDER_NAME => "customSenderName",
]);
```

## Usage
### Configuration
[ConfigInterface](./src/ConfigInterface.php) have to be used to configure requests.
Available implementations:
- [Config](./src/Config.php) - simple implementation using class properties
- [EnvironmentConfig](./src/EnvironmentConfig.php) - loads configuration values from environment using 
[getenv](http://php.net/manual/ru/function.getenv.php)

### Additional methods
Besides implementing Delivery\ServiceInterface [Service](./src/Service.php) provides

```php
<?php

use Wearesho\Delivery;

$service = new Delivery\TurboSms\Service(
    new Delivery\TurboSms\Config('httpToken', 'senderName (alpha name)'),
);
```

- Send sms
```php
<?php

use Wearesho\Delivery;

/** @var Delivery\TurboSms\Service $service */

$service->send(
    new Delivery\Message('Message', '+380000000000')
);
```

- Check balance on current account
```php
<?php

use Wearesho\Delivery;

/** @var Delivery\TurboSms\Service $service */

$balance = $service->balance();
$balance->getAmount();
$balance->getCurrency();

$message = (string)$balance; // will output "{amount} Credits"
```

### Sender Name Configuration

The library supports different sender names for SMS and Viber channels:

#### Priority Order (highest to lowest):
1. **Message-level sender** - Set via options, applies to all channels
2. **Viber-specific sender** - Set via config for Viber channel only
3. **Default sender** - Fallback for all channels

#### Example with Separate Viber Sender:

```php
<?php
use Wearesho\Delivery;
use Wearesho\Delivery\TurboSms;

// Configuration
$config = new TurboSms\Config(
    httpToken: 'your-token',
    senderName: 'SmsSender',
    viberSenderName: 'ViberSender'
);

$service = new TurboSms\Service(new GuzzleHttp\Client(), $config);

// SMS uses 'SmsSender', Viber uses 'ViberSender'
$service->send(new Delivery\Message('Multi text', '+380970000000', [
    'channel' => ['sms', 'viber']
]));

// Message-level override: both channels use 'CustomSender'
$service->send(new Delivery\Message('Override text', '+380970000000', [
    'channel' => ['sms', 'viber'],
    'senderName' => 'CustomSender'
]));
```

#### Environment Configuration:
```bash
TURBOSMS_HTTP_TOKEN=your-token
TURBOSMS_SENDER=SmsSender
TURBOSMS_VIBER_SENDER=ViberSender
```

## Authors
- [Roman <KartaviK> Varkuta](mailto:roman.varkuta@gmail.com)
- [Oleksander Letnikov](mailto:reclamme@gmail.com)

## License
[MIT](./LICENSE)
