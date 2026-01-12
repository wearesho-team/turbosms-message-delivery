<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms;

enum ViberOptions: string
{
    case IMAGE_URL = 'viberImageUrl';
    case BUTTON_TEXT = 'viberButtonText';
    case BUTTON_URL = 'viberButtonUrl';
    case IS_TRANSACTIONAL = 'isTransactional';

    public function getRequestParameter(): string
    {
        return match ($this) {
            ViberOptions::BUTTON_URL => 'action',
            ViberOptions::BUTTON_TEXT => 'caption',
            ViberOptions::IMAGE_URL => 'image_url',
            ViberOptions::IS_TRANSACTIONAL => 'is_transactional',
        };
    }
}
