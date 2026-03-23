<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Enums;

use Support\Routing\Enums\Method;

enum ActionMethod: string
{
    case Post = Method::Post->value;
}
