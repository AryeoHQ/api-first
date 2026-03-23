<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Enums;

enum Scope: string
{
    case Resource = 'resource';

    case Instance = 'instance';
}
