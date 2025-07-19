<?php

namespace App\Enums;

use App\Traits\TranslatableEnumTrait;

enum Source: string
{
    use TranslatableEnumTrait;
    case MANUAL_VIA_FORM = 'FORM';
    case MANUAL_VIA_FILE_UPLOAD = 'FILE';
    case PUBLIC_API = 'API';
}
