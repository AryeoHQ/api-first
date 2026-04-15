<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Resources\Json;

use Illuminate\Http\Resources\Json\JsonResource;
use Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Provides\WithStructuredMeta;
use Support\Http\Resources\Schemas\Attributes\CollectedBy;
use Support\Http\Resources\Schemas\Contracts\Schema;
use Support\Http\Resources\Schemas\Provides\AsSchema;

#[CollectedBy(Posts::class)]
class Post extends JsonResource implements Schema
{
    use AsSchema;
    use WithStructuredMeta;

    public int $id { get => $this->resource['id']; }

    public string $resourceType = 'post';
}
