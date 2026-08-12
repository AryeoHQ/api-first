<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Resources\Json;

use Illuminate\Http\Resources\Json\JsonResource;
use Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Provides\WithStructuredMeta;
use Support\Http\Resources\Schemas\Attributes\CollectedBy\CollectedBy;
use Support\Http\Resources\Schemas\Attributes\Version\Version;
use Support\Http\Resources\Schemas\Contracts\Schema;
use Support\Http\Resources\Schemas\Provides\AsSchema;
use Tests\Fixtures\Support\Schemas\ApiVersion;

#[CollectedBy(Posts::class)]
#[Version(ApiVersion::V1)]
class Post extends JsonResource implements Schema
{
    /** @use AsSchema<ApiVersion> */
    use AsSchema;

    use WithStructuredMeta;

    public int $id { get => $this->resource['id']; }

    public string $resourceType = 'post';
}
