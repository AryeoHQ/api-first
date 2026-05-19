<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Posts;

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
use Tests\Fixtures\Support\Entities\Posts\Builder\Builder;
use Tests\Fixtures\Support\Entities\Posts\Collection\Posts;
use Tests\Fixtures\Support\Entities\Posts\Factory\Factory;
use Tests\Fixtures\Support\Entities\Posts\Policy\Policy;
use Tests\Fixtures\Support\Http\Api\Resources\Json\Post as V1Post;

#[CollectedBy(Posts::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
#[UsePolicy(Policy::class)]
#[UseSchema(V1Post::class)]
class Post extends Model implements Entity, Loggable, Schemable
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;
    use LogsSchemas;
}
