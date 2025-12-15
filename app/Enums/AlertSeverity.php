<?php

namespace App\Enums;

enum AlertSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
