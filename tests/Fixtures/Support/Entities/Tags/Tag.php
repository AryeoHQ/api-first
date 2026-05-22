<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Tags;

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
use Support\Http\Resources\Schemas\Contracts\Schemable;
use Tests\Fixtures\Support\Entities\Tags\Builder\Builder;
use Tests\Fixtures\Support\Entities\Tags\Collection\Tags;
use Tests\Fixtures\Support\Entities\Tags\Factory\Factory;
use Tests\Fixtures\Support\Entities\Tags\Policy\Policy;

#[CollectedBy(Tags::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
#[UsePolicy(Policy::class)]
class Tag extends Model implements Entity, Loggable, Schemable
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;
    use LogsSchemas;
}
