<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Support\Database\Eloquent\Concurrency\Contracts\NamesConcurrencyRevision;

enum NoteRevision: string implements NamesConcurrencyRevision
{
    case ChildrenOrder = 'children_order';
}
