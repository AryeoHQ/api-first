<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions\Schemas\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Support\Http\Resources\Schemas\Attributes\CollectedBy\CollectedBy;
use Support\Http\Resources\Schemas\Attributes\Version\Version;
use Support\Http\Resources\Schemas\Contracts\Schema;
use Support\Http\Resources\Schemas\Provides\AsSchema;
use Tests\Fixtures\Support\Schemas\ApiVersion;

#[CollectedBy(SecondaryNotes::class)]
#[Version(ApiVersion::V1)]
final class SecondaryNote extends JsonResource implements Schema
{
    /** @use AsSchema<ApiVersion> */
    use AsSchema;

    public null|string $id { get => $this->resource->getKey(); }

    public string $resourceType = 'secondary_note';

    public string $title { get => $this->resource->title; }
}
