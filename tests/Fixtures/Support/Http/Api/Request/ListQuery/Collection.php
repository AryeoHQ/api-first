<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Request\ListQuery;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Provides\WithDirectionalMeta;
use Support\Http\Resources\Schemas\Attributes\Collects\Collects;
use Support\Http\Resources\Schemas\Contracts\SchemaCollection;
use Support\Http\Resources\Schemas\Provides\AsSchemaCollection;

#[Collects(Resource::class)]
final class Collection extends ResourceCollection implements SchemaCollection
{
    use AsSchemaCollection;
    use WithDirectionalMeta;
}
