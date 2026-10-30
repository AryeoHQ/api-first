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
use Illuminate\Database\Eloquent\SoftDeletes;
use Support\Database\Eloquent\Concurrency\Contracts\LocksParentsFirst;
use Support\Database\Eloquent\Concurrency\Contracts\Revisioned;
use Support\Database\Eloquent\Concurrency\Provides\HasConcurrencyRevisions;
use Support\Entities\Contracts\Entity;

#[CollectedBy(Notes::class)]
#[UseEloquentBuilder(NoteBuilder::class)]
#[UseFactory(NoteFactory::class)]
#[UsePolicy(NotePolicy::class)]
final class NoteWithParentLock extends Model implements Entity, LocksParentsFirst, Revisioned
{
    use HasConcurrencyRevisions;

    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $table = 'notes';

    protected $fillable = ['title', 'parent_id'];

    /** @var list<array{type: string, id: string}> */
    public static array $lockCalls = [];

    public function getMorphClass(): string
    {
        return 'note';
    }

    public function lockParentsFirst(): void
    {
        self::$lockCalls[] = ['type' => 'parent', 'id' => $this->getKey()];
    }
}
