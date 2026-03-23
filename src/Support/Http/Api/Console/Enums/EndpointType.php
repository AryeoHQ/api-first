<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Enums;

enum EndpointType: string
{
    case Rest = 'REST';

    case Action = 'Action';
}
