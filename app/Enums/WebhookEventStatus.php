<?php

namespace App\Enums;

enum WebhookEventStatus: string
{
    case PENDING = "pending";
    case PROCESSING = "processing";
    case PROCESSED = "processed";
    case FAILED = "failed";

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
