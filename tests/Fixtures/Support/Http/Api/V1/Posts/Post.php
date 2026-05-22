<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\V1\Posts;

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

    public string $id { get => $this->resource->getKey(); }

    public string $resourceType = 'post';

    public ApiVersion $resourceVersion { get => $this->schemaVersion; }
}
