<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Articles\Schemas\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Support\Http\Resources\Schemas\Attributes\CollectedBy\CollectedBy;
use Support\Http\Resources\Schemas\Attributes\Version\Version;
use Support\Http\Resources\Schemas\Contracts\Schema;
use Support\Http\Resources\Schemas\Provides\AsSchema;
use Tests\Fixtures\Support\Schemas\ApiVersion;

#[CollectedBy(Articles::class)]
#[Version(ApiVersion::V1)]
final class Article extends JsonResource implements Schema
{
    /** @use AsSchema<ApiVersion> */
    use AsSchema;

    public null|string $id { get => $this->resource->getKey(); }

    public string $resourceType = 'article';
}
