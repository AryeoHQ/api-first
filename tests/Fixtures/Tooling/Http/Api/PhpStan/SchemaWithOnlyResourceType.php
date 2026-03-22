<?php

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan;

use Illuminate\Http\Resources\Json\JsonResource;
use Support\Http\Resources\Schemas\Contracts\Schema;
use Support\Http\Resources\Schemas\Provides\AsSchema;

final class SchemaWithOnlyResourceType extends JsonResource implements Schema
{
    use AsSchema;

    public string $resourceType = 'example';
}
