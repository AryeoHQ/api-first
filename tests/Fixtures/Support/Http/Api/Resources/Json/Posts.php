<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Resources\Json;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Provides\WithStructuredMeta;
use Support\Http\Resources\Schemas\Attributes\Collects;
use Support\Http\Resources\Schemas\Contracts\SchemaCollection;
use Support\Http\Resources\Schemas\Provides\AsSchemaCollection;

#[Collects(Post::class)]
class Posts extends ResourceCollection implements SchemaCollection
{
    use AsSchemaCollection;
    use WithStructuredMeta;
}
