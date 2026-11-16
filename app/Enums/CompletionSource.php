<?php

namespace App\Enums;

enum CompletionSource: string
{
    case Manual = 'manual';
    case Exercise = 'exercise';
    case Quiz = 'quiz';
}
