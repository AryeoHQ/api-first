<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Models\Concerns;

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

#[CollectedBy(LogsSchemasCollection::class)]
#[UseEloquentBuilder(LogsSchemasBuilder::class)]
#[UseFactory(LogsSchemasFactory::class)]
#[UsePolicy(LogsSchemasPolicy::class)]
#[UseSchema(LogsSchemasSchema::class)]
class LogsSchemasModel extends Model implements Entity, Loggable, Schemable
{
    /** @use HasFactory<LogsSchemasFactory> */
    use HasFactory;

    use HasUuids;
    use LogsSchemas;
}
