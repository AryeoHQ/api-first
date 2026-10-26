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

#[CollectedBy(RelationCollection::class)]
#[Version(ApiVersion::V1)]
final class RelationResource extends JsonResource implements Schema
{
    /** @use AsSchema<ApiVersion> */
    use AsSchema;

    use WithDirectionalMeta;

    public null|string $id { get => $this->resource->getKey(); }

    public null|string $createdAt { get => $this->resource->created_at?->toJSON(); }

    public null|int $pivotId { get => $this->resource->relationLoaded('pivot') ? $this->resource->pivot->id : null; }

    public string $resourceType = 'list_query_relation_item';
}
