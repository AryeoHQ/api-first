<?php

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan;

use Illuminate\Http\Resources\Json\JsonResource;
use Support\Http\Resources\Schemas\Contracts;
use Support\Http\Resources\Schemas\Provides\AsSchema;

final class Schema extends JsonResource implements Contracts\Schema
{
    use AsSchema;

    public string $id { get => $this->resource->getKey(); }

    public string $resourceType = 'example';
}
