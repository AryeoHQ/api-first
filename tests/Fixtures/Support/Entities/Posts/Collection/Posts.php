<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Posts\Collection;

use Illuminate\Database\Eloquent\Collection;
use Support\Http\Resources\Schemas\Attributes\UseSchemaCollection\UseSchemaCollection;
use Support\Http\Resources\Schemas\Concerns\TransformsToSchemaCollection;
use Support\Http\Resources\Schemas\Contracts\SchemableCollection;
use Tests\Fixtures\Support\Http\Api\Resources\Json\Posts as V1Posts;

/**
 * @extends Collection<int, \Tests\Fixtures\Support\Entities\Posts\Post>
 */
#[UseSchemaCollection(V1Posts::class)]
final class Posts extends Collection implements SchemableCollection
{
    use TransformsToSchemaCollection;
}
