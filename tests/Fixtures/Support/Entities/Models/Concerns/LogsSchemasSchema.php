<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Models\Concerns;

use Illuminate\Http\Resources\Json\JsonResource;
use Support\Http\Resources\Schemas\Attributes\CollectedBy\CollectedBy;
use Support\Http\Resources\Schemas\Attributes\Version\Version;
use Support\Http\Resources\Schemas\Contracts\Schema;
use Support\Http\Resources\Schemas\Provides\AsSchema;
use Tests\Fixtures\Support\Schemas\ApiVersion;

#[CollectedBy(LogsSchemasSchemas::class)]
#[Version(ApiVersion::V1)]
final class LogsSchemasSchema extends JsonResource implements Schema
{
    /** @use AsSchema<ApiVersion> */
    use AsSchema;

    public string $id { get => $this->resource->getKey(); }

    public string $resourceType = 'logs-schemas-model';
}
