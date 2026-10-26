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
use Support\Database\Eloquent\Concurrency\Contracts\Revisioned;
use Support\Database\Eloquent\Concurrency\Provides\HasConcurrencyRevisions;
use Support\Entities\Contracts\Entity;
use Support\Http\Resources\Schemas\Attributes\UseSchema\UseSchema;
use Support\Http\Resources\Schemas\Concerns\TransformsToSchema;
use Support\Http\Resources\Schemas\Contracts\Schemable;

/**
 * @property string $title
 */
#[CollectedBy(SecondaryNotes::class)]
#[UseEloquentBuilder(SecondaryNoteBuilder::class)]
#[UseFactory(SecondaryNoteFactory::class)]
#[UsePolicy(SecondaryNotePolicy::class)]
#[UseSchema(Schemas\V1\SecondaryNote::class)]
final class SecondaryNote extends Model implements Entity, Revisioned, Schemable
{
    use HasConcurrencyRevisions;

    /** @use HasFactory<SecondaryNoteFactory> */
    use HasFactory;

    use HasUuids;
    use TransformsToSchema;

    protected $connection = 'secondary';

    protected $fillable = ['title'];

    public function getMorphClass(): string
    {
        return 'auxiliary_note';
    }
}
