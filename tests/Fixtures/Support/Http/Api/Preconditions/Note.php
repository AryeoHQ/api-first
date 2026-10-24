<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Support\Database\Eloquent\Concurrency\Attributes\NamedConcurrencyRevision;
use Support\Database\Eloquent\Concurrency\Contracts\Revisioned;
use Support\Database\Eloquent\Concurrency\Provides\HasConcurrencyRevisions;
use Support\Entities\Contracts\Entity;
use Support\Http\Resources\Schemas\Attributes\UseSchema\UseSchema;
use Support\Http\Resources\Schemas\Concerns\TransformsToSchema;
use Support\Http\Resources\Schemas\Contracts\Schemable;

/**
 * @property string $title
 */
#[CollectedBy(Notes::class)]
#[NamedConcurrencyRevision(NoteRevision::ChildrenOrder)]
#[UseEloquentBuilder(NoteBuilder::class)]
#[UseFactory(NoteFactory::class)]
#[UsePolicy(NotePolicy::class)]
#[UseSchema(Schemas\V1\Note::class)]
final class Note extends Model implements Entity, Revisioned, Schemable
{
    use HasConcurrencyRevisions;

    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;
    use TransformsToSchema;

    protected $fillable = ['title', 'parent_id'];

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
