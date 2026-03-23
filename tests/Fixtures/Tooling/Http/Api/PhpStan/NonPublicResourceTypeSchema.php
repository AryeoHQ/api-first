<?php

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan;

use Illuminate\Http\Resources\Json\JsonResource;
use Support\Http\Resources\Schemas\Contracts\Schema as SchemaContract;
use Support\Http\Resources\Schemas\Provides\AsSchema;

final class NonPublicResourceTypeSchema extends JsonResource implements SchemaContract
{
    use AsSchema;

    public string $id { get => $this->resource->getKey(); }

    protected string $resourceType = 'example';
}
