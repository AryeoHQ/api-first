<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Support\Database\Eloquent\Concurrency\Contracts\NamesConcurrencyRevision;

enum UndeclaredRevision: string implements NamesConcurrencyRevision
{
    case Attachments = 'attachments';
}
