<?php

namespace App\Enums;

enum WebhookErrorTypeEnum: string
{
    case HTTP_400_ERROR = 'HTTP-400-Error';
    case HTTP_500_ERROR = 'HTTP-500-Error';
}
