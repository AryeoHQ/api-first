<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Articles\Schemas\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Support\Http\Resources\Schemas\Attributes\Collects\Collects;
use Support\Http\Resources\Schemas\Contracts\SchemaCollection;
use Support\Http\Resources\Schemas\Provides\AsSchemaCollection;

#[Collects(Article::class)]
final class Articles extends ResourceCollection implements SchemaCollection
{
    use AsSchemaCollection;
}
