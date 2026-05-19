<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Models\Concerns;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Support\Http\Resources\Schemas\Attributes\Collects\Collects;
use Support\Http\Resources\Schemas\Contracts\SchemaCollection;
use Support\Http\Resources\Schemas\Provides\AsSchemaCollection;

#[Collects(LogsSchemasSchema::class)]
final class LogsSchemasSchemas extends ResourceCollection implements SchemaCollection
{
    use AsSchemaCollection;
}
