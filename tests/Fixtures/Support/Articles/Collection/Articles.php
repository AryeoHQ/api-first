<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Articles\Collection;

use Illuminate\Database\Eloquent\Collection;
use Support\Http\Resources\Schemas\Attributes\UseSchemaCollection\UseSchemaCollection;
use Support\Http\Resources\Schemas\Concerns\TransformsToSchemaCollection;
use Support\Http\Resources\Schemas\Contracts\SchemableCollection;
use Tests\Fixtures\Support\Articles\Schemas\V1;

/**
 * @extends Collection<int, \Tests\Fixtures\Support\Articles\Article>
 */
#[UseSchemaCollection(V1\Articles::class)]
final class Articles extends Collection implements SchemableCollection
{
    use TransformsToSchemaCollection;
}
