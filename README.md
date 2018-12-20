# TurboSMS Integration

[![Latest Stable Version](https://poser.pugx.org/wearesho-team/turbosms-message-delivery/v/stable.png)](https://packagist.org/packages/wearesho-team/turbosms-message-delivery)
[![Total Downloads](https://poser.pugx.org/wearesho-team/turbosms-message-delivery/downloads.png)](https://packagist.org/packages/wearesho-team/turbosms-message-delivery)
[![Build Status](https://travis-ci.org/wearesho-team/turbosms-message-delivery.svg?branch=master)](https://travis-ci.org/wearesho-team/turbosms-message-delivery)
[![codecov](https://codecov.io/gh/wearesho-team/turbosms-message-delivery/branch/master/graph/badge.svg)](https://codecov.io/gh/wearesho-team/turbosms-message-delivery)

[wearesho-team/message-delivery](https://github.com/wearesho-team/message-delivery) implementation of
[Delivery\ServiceInterface](https://github.com/wearesho-team/message-delivery/blob/1.3.4/src/ServiceInterface.php)

## Installation
```bash
composer require wearsho-team/turbosms-message-delivery
```

## Usage
### Configuration
[ConfigInterface](./src/ConfigInterface.php) have to be used to configure requests.
Available implementations:
- [Config](./src/Config.php) - simple implementation using class properties
- [EnvironmentConfig](./src/EnvironmentConfig.php) - loads configuration values from environment using 
[getenv](http://php.net/manual/ru/function.getenv.php)


| Variable | Required | Description |
|----------|----------|-------------|
| TURBOSMS_LOGIN | Yes | Your login to gateway |
| TURBOSMS_PASSWORD | Yes | Your password to gateway |
| TURBOSMS_SENDER | no | Sender name, that was declared in your account |
| TURBOSMS_URI | no | Uri to wsdl document |

### Additional methods
Besides implementing Delivery\ServiceInterface [Service](./src/Service.php) provides

**Important!** Cookies required enabled on server
```php
<?php

use Wearesho\Delivery;

$service = new Delivery\TurboSms\Service(
    new Delivery\TurboSms\Config('login', 'password'),
    new GuzzleHttp\Client(['cookies' => true])
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

## Authors
- [Roman <KartaviK> Varkuta](mailto:roman.varkuta@gmail.com)

## License
[MIT](./LICENSE)
