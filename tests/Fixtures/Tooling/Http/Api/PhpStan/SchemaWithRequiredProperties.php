<?php

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan;

use Illuminate\Http\Resources\Json\JsonResource;
use Support\Http\Resources\Schemas\Contracts\Schema;
use Support\Http\Resources\Schemas\Provides\AsSchema;

final class SchemaWithRequiredProperties extends JsonResource implements Schema
{
    use AsSchema;

    public string $id { get => $this->resource->getKey(); }

    public string $resourceType = 'example';
}
