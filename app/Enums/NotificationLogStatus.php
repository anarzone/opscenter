<?php

namespace App\Enums;

enum NotificationLogStatus: string
{
    case SENT = "sent";
    case FAILED = "failed";

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
