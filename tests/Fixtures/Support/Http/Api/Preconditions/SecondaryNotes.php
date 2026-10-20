<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Database\Eloquent\Collection;
use Support\Http\Resources\Schemas\Attributes\UseSchemaCollection\UseSchemaCollection;
use Support\Http\Resources\Schemas\Concerns\TransformsToSchemaCollection;
use Support\Http\Resources\Schemas\Contracts\SchemableCollection;

/**
 * @extends Collection<int, SecondaryNote>
 */
#[UseSchemaCollection(Schemas\V1\SecondaryNotes::class)]
final class SecondaryNotes extends Collection implements SchemableCollection
{
    use TransformsToSchemaCollection;
}
