<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Models\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Support\Http\Resources\Schemas\Attributes\UseSchemaCollection\UseSchemaCollection;
use Support\Http\Resources\Schemas\Concerns\TransformsToSchemaCollection;
use Support\Http\Resources\Schemas\Contracts\SchemableCollection;

/**
 * @extends Collection<int, LogsSchemasModel>
 */
#[UseSchemaCollection(LogsSchemasSchemas::class)]
final class LogsSchemasCollection extends Collection implements SchemableCollection
{
    use TransformsToSchemaCollection;
}
