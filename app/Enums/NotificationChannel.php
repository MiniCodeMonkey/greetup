<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Email = 'email';
    case Web = 'web';
    case Push = 'push';
}
