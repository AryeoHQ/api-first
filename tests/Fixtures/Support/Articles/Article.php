<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Articles;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Support\Entities\Contracts\Entity;
use Support\Entities\Models\Concerns\LogsSchemas;
use Support\Events\Log\Contracts\Loggable;
use Support\Http\Resources\Schemas\Attributes\UseSchema\UseSchema;
use Support\Http\Resources\Schemas\Contracts\Schemable;
use Tests\Fixtures\Support\Articles\Collection\Articles;
use Tests\Fixtures\Support\Articles\Schemas\V1;

#[CollectedBy(Articles::class)]
#[UseEloquentBuilder(Builder\Builder::class)]
#[UseFactory(Factory\Factory::class)]
#[UsePolicy(Policy\Policy::class)]
#[UseSchema(V1\Article::class)]
class Article extends Model implements Entity, Loggable, Schemable
{
    /** @use HasFactory<Factory\Factory> */
    use HasFactory;

    use HasUuids;
    use LogsSchemas;
}
