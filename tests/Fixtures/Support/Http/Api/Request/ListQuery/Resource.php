<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Request\ListQuery;

use Illuminate\Http\Resources\Json\JsonResource;
use Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Provides\WithDirectionalMeta;
use Support\Http\Resources\Schemas\Attributes\CollectedBy\CollectedBy;
use Support\Http\Resources\Schemas\Attributes\Version\Version;
use Support\Http\Resources\Schemas\Contracts\Schema;
use Support\Http\Resources\Schemas\Provides\AsSchema;
use Tests\Fixtures\Support\Schemas\ApiVersion;

#[CollectedBy(Collection::class)]
#[Version(ApiVersion::V1)]
final class Resource extends JsonResource implements Schema
{
    /** @use AsSchema<ApiVersion> */
    use AsSchema;

    use WithDirectionalMeta;

    public null|string $id { get => $this->resource->getKey(); }

    public string $resourceType = 'list_query_item';
}
