<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Schemas;

use Support\Events\Log\Logs;
use Support\Http\Resources\Schemas;

enum ApiVersion: string implements Logs\Data\Version\Contracts\Version, Schemas\Contracts\Version
{
    case V1 = 'v1';
}
