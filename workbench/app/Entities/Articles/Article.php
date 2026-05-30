<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles;

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
use Workbench\App\Entities\Articles\Actions\Syndicate;
use Workbench\App\Entities\Articles\Builder\Builder;
use Workbench\App\Entities\Articles\Collection\Articles;
use Workbench\App\Entities\Articles\Factory\Factory;
use Workbench\App\Entities\Articles\Policy\Policy;
use Workbench\App\Http\Api\V1;

#[CollectedBy(Articles::class)]
#[UseEloquentBuilder(Builder::class)]
#[UseFactory(Factory::class)]
#[UsePolicy(Policy::class)]
#[UseSchema(V1\Articles\Article::class)]
class Article extends Model implements Entity, Loggable, Schemable
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;
    use LogsSchemas;

    protected $fillable = [
        'title',
        'body',
        'syndicated_at',
    ];

    protected $casts = [
        'syndicated_at' => 'datetime',
    ];

    /**
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'retrieved' => Events\Retrieved::class,
        'creating' => Events\Creating::class,
        'created' => Events\Created::class,
        'updating' => Events\Updating::class,
        'updated' => Events\Updated::class,
        'saving' => Events\Saving::class,
        'saved' => Events\Saved::class,
        'restoring' => Events\Restoring::class,
        'restored' => Events\Restored::class,
        'replicating' => Events\Replicating::class,
        'trashed' => Events\Trashed::class,
        'deleting' => Events\Deleting::class,
        'deleted' => Events\Deleted::class,
        'forceDeleting' => Events\ForceDeleting::class,
        'forceDeleted' => Events\ForceDeleted::class,
    ];

    public function syndicate(): Syndicate
    {
        return Syndicate::make($this);
    }
}
