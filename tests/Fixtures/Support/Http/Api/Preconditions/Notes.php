<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Database\Eloquent\Collection;
use Support\Http\Resources\Schemas\Attributes\UseSchemaCollection\UseSchemaCollection;
use Support\Http\Resources\Schemas\Concerns\TransformsToSchemaCollection;
use Support\Http\Resources\Schemas\Contracts\SchemableCollection;

/**
 * @extends Collection<int, Note>
 */
#[UseSchemaCollection(Schemas\V1\Notes::class)]
final class Notes extends Collection implements SchemableCollection
{
    use TransformsToSchemaCollection;
}
