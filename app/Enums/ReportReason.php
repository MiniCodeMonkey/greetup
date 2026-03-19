<?php

namespace App\Enums;

enum ReportReason: string
{
    case Spam = 'spam';
    case Harassment = 'harassment';
    case HateSpeech = 'hate_speech';
    case Impersonation = 'impersonation';
    case InappropriateContent = 'inappropriate_content';
    case Misleading = 'misleading';
    case Other = 'other';
}
