<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions\Targets;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Support\Database\Eloquent\Concurrency\ConcurrencyRevision;
use Support\Database\Eloquent\Concurrency\Exceptions\UndeclaredConcurrencyRevision;
use Support\Database\Eloquent\Concurrency\Revisions;
use Support\Http\Api\Preconditions\Exceptions\ParentsChanged;
use Tests\Fixtures\Support\Http\Api\Preconditions\Note;
use Tests\Fixtures\Support\Http\Api\Preconditions\NoteRevision;
use Tests\Fixtures\Support\Http\Api\Preconditions\NoteWithParentLock;
use Tests\Fixtures\Support\Http\Api\Preconditions\SecondaryNote;
use Tests\Fixtures\Support\Http\Api\Preconditions\ShadowingRevision;
use Tests\Fixtures\Support\Http\Api\Preconditions\UndeclaredRevision;
use Tests\Fixtures\Support\Schemas\ApiVersion;
use Tests\TestCase;

#[CoversClass(EntityTarget::class)]
#[WithConfig('database.connections.secondary', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''])]
final class EntityTargetTest extends TestCase
{
    use WithWorkbench;

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('notes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->nullable();
            $table->string('title');
            $table->concurrencyRevision();
            $table->namedConcurrencyRevision(NoteRevision::ChildrenOrder->value);
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('secondary_notes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->concurrencyRevision();
            $table->timestampsTz();
        });
    }

    #[Test]
    public function it_refuses_to_compare_an_empty_set(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->expectException(LogicException::class);

        EntityTarget::for($note, ApiVersion::V1)->compared();
    }

    #[Test]
    public function it_compares_the_own_revision(): void
    {
        $note = $this->note(self: 3, childrenOrder: 5);

        $this->assertEquals(Revisions::of(3), EntityTarget::for($note, ApiVersion::V1)->comparingSelf()->compared());
    }

    #[Test]
    public function it_compares_only_the_named_revisions(): void
    {
        $note = $this->note(self: 3, childrenOrder: 5);

        $this->assertEquals(Revisions::of(null, ['children_order' => 5]), EntityTarget::for($note, ApiVersion::V1)->comparingNamed(NoteRevision::ChildrenOrder)->compared());
    }

    #[Test]
    public function it_adds_each_comparison_to_the_set(): void
    {
        $note = $this->note(self: 3, childrenOrder: 5);

        $this->assertEquals(
            Revisions::of(3, ['children_order' => 5]),
            EntityTarget::for($note, ApiVersion::V1)->comparingSelf()->comparingNamed(NoteRevision::ChildrenOrder)->compared(),
        );
    }

    #[Test]
    public function it_compares_every_declared_revision(): void
    {
        $note = $this->note(self: 3, childrenOrder: 5);

        $this->assertEquals(Revisions::of(3, ['children_order' => 5]), EntityTarget::for($note, ApiVersion::V1)->comparingAll()->compared());
    }

    #[Test]
    public function it_refuses_a_revision_the_model_does_not_declare(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->expectException(UndeclaredConcurrencyRevision::class);

        EntityTarget::for($note, ApiVersion::V1)->comparingNamed(UndeclaredRevision::Attachments)->compared();
    }

    #[Test]
    public function it_refuses_an_application_revision_named_like_the_own_revision(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->expectException(UndeclaredConcurrencyRevision::class);

        EntityTarget::for($note, ApiVersion::V1)->comparingNamed(ShadowingRevision::Own)->compared();
    }

    #[Test]
    public function it_refuses_to_compare_a_named_revision_whose_column_is_not_loaded(): void
    {
        $note = $this->partialNote();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('is not loaded');

        EntityTarget::for($note, ApiVersion::V1)->comparingNamed(NoteRevision::ChildrenOrder)->compared();
    }

    #[Test]
    public function it_refuses_to_compare_every_revision_when_a_column_is_not_loaded(): void
    {
        $note = $this->partialNote();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('are not loaded');

        EntityTarget::for($note, ApiVersion::V1)->comparingAll()->compared();
    }

    #[Test]
    public function it_compares_a_revision_whose_column_is_loaded_when_another_is_not(): void
    {
        $note = $this->partialNote();

        $this->assertEquals(Revisions::of(1), EntityTarget::for($note, ApiVersion::V1)->comparingSelf()->compared());
    }

    #[Test]
    public function it_names_the_api_version_and_the_connection_of_the_model(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $target = EntityTarget::for($note, ApiVersion::V1);

        $this->assertSame(ApiVersion::V1, $target->apiVersion());
        $this->assertSame($note->getConnection(), $target->connection());
    }

    #[Test]
    public function it_compares_the_committed_revisions_once_it_locks(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $target = EntityTarget::for($note, ApiVersion::V1)->comparingAll();

        DB::table('notes')->where('id', $note->getKey())->update(['title' => 'Final', ConcurrencyRevision::COLUMN => 2]);

        $this->assertEquals(Revisions::of(1, ['children_order' => 1]), $target->compared());

        $target->lock();

        $this->assertEquals(Revisions::of(2, ['children_order' => 1]), $target->compared());
        $this->assertSame('Final', $note->title);
        $this->assertFalse($note->isDirty());
    }

    #[Test]
    public function it_locks_every_row_in_primary_key_order(): void
    {
        [$low, $middle, $high] = Note::query()->find([
            Note::query()->create(['title' => 'First'])->getKey(),
            Note::query()->create(['title' => 'Second'])->getKey(),
            Note::query()->create(['title' => 'Third'])->getKey(),
        ])->sortBy(fn (Note $note): mixed => $note->getKey())->values()->all();
        $locked = [];

        Event::listen(function (QueryExecuted $query) use (&$locked): void {
            $locked[] = $query->bindings[0];
        });

        EntityTarget::for($middle, ApiVersion::V1)->alsoLocking($high, $low)->lock();

        $this->assertSame([$low->getKey(), $middle->getKey(), $high->getKey()], $locked);
    }

    #[DataProvider('keyOrders')]
    #[Test]
    public function it_locks_models_of_different_classes_in_morph_class_order_whatever_their_keys(string $noteKey, string $auxiliaryKey): void
    {
        $note = (new Note(['title' => 'Draft']))->forceFill(['id' => $noteKey]);
        $note->save();
        $auxiliary = (new SecondaryNote(['title' => 'Draft']))->setConnection(null)->forceFill(['id' => $auxiliaryKey]);
        $auxiliary->save();
        $locked = [];

        Event::listen(function (QueryExecuted $query) use (&$locked): void {
            $locked[] = $query->bindings[0];
        });

        EntityTarget::for($note, ApiVersion::V1)->alsoLocking($auxiliary)->lock();

        $this->assertSame([$auxiliaryKey, $noteKey], $locked);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function keyOrders(): array
    {
        $low = '00000000-0000-4000-8000-000000000001';
        $high = 'ffffffff-ffff-4fff-8fff-ffffffffffff';

        return [
            'the note has the lower key' => [$low, $high],
            'the auxiliary note has the lower key' => [$high, $low],
        ];
    }

    #[Test]
    public function it_loads_the_committed_row_of_each_extra_model_when_it_locks(): void
    {
        $note = Note::query()->create(['title' => 'Destination']);
        $source = Note::query()->create(['title' => 'Source']);

        DB::table('notes')->where('id', $source->getKey())->update(['title' => 'Renamed', ConcurrencyRevision::COLUMN => 4]);

        EntityTarget::for($note, ApiVersion::V1)->alsoLocking($source)->lock();

        $this->assertSame('Renamed', $source->title);
        $this->assertSame(4, $source->concurrencyRevision());
        $this->assertFalse($source->isDirty());
    }

    #[Test]
    public function it_refuses_to_also_lock_a_model_on_another_connection(): void
    {
        $note = Note::query()->create(['title' => 'Primary']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('on connection [secondary]');

        EntityTarget::for($note, ApiVersion::V1)->alsoLocking(new SecondaryNote(['title' => 'Other']));
    }

    #[Test]
    public function it_also_locks_a_model_that_names_the_connection_of_the_target(): void
    {
        $note = Note::query()->create(['title' => 'Primary'])->setConnection(null);
        $other = Note::query()->create(['title' => 'Other']);

        $this->assertNotSame($note->getConnectionName(), $other->getConnectionName());

        EntityTarget::for($note, ApiVersion::V1)->alsoLocking($other)->lock();

        $this->assertSame('Other', $other->title);
    }

    #[Test]
    public function it_keeps_a_constrained_relation_when_it_locks(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        Note::query()->create(['title' => 'Public', 'parent_id' => $note->getKey()]);
        Note::query()->create(['title' => 'Private', 'parent_id' => $note->getKey()]);
        $note->load(['children' => fn ($query) => $query->where('title', 'Public')]);

        EntityTarget::for($note, ApiVersion::V1)->lock();

        $this->assertSame(['Public'], $note->children->pluck('title')->all());
    }

    #[Test]
    public function it_keeps_a_nested_relation_when_it_locks(): void
    {
        $grandparent = Note::query()->create(['title' => 'Grandparent']);
        $parent = Note::query()->create(['title' => 'Parent', 'parent_id' => $grandparent->getKey()]);
        $note = Note::query()->create(['title' => 'Child', 'parent_id' => $parent->getKey()])->load('parent.parent');

        EntityTarget::for($note, ApiVersion::V1)->lock();

        $this->assertTrue($note->parent?->relationLoaded('parent'));
        $this->assertSame('Grandparent', $note->parent->parent?->title);
    }

    #[Test]
    public function it_drops_a_loaded_parent_whose_key_changed_before_the_lock(): void
    {
        $old = Note::query()->create(['title' => 'Old']);
        $new = Note::query()->create(['title' => 'New']);
        $note = Note::query()->create(['title' => 'Child', 'parent_id' => $old->getKey()])->load('parent');

        DB::table('notes')->where('id', $note->getKey())->update(['parent_id' => $new->getKey()]);

        EntityTarget::for($note, ApiVersion::V1)->lock();

        $this->assertSame('New', $note->parent?->title);
    }

    #[Test]
    public function it_keeps_a_pivot_when_it_locks(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $pivot = new Pivot;
        $note->setRelation('pivot', $pivot);

        EntityTarget::for($note, ApiVersion::V1)->lock();

        $this->assertSame($pivot, $note->getRelation('pivot'));
    }

    #[Test]
    public function it_fails_to_lock_a_row_that_no_longer_exists(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        Note::query()->whereKey($note->getKey())->forceDelete();

        $this->expectException(ModelNotFoundException::class);

        EntityTarget::for($note, ApiVersion::V1)->lock();
    }

    #[Test]
    public function it_selects_the_row_for_update_when_it_locks(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $connection = DB::connection();
        $connection->setQueryGrammar(new PostgresGrammar($connection));

        $queries = $connection->pretend(function () use ($note): void {
            rescue(fn () => EntityTarget::for($note, ApiVersion::V1)->lock(), report: false);
        });

        $this->assertSame("select * from \"notes\" where \"notes\".\"id\" = '{$note->getKey()}' and \"notes\".\"deleted_at\" is null limit 1 for update", $queries[0]['query']);
    }

    #[Test]
    public function it_locks_the_parents_of_every_model_in_parent_order_rather_than_entity_order(): void
    {
        $first = $this->parentNote(1);
        $second = $this->parentNote(2);
        $inSecond = $this->noteWithParentLock(3, parent: $second);
        $inFirst = $this->noteWithParentLock(4, parent: $first);
        $locked = $this->recordLocks();

        EntityTarget::for($inSecond, ApiVersion::V1)->alsoLocking($inFirst)->lock();

        $this->assertSame([$first, $second, $inSecond->getKey(), $inFirst->getKey()], $locked->pluck('key')->all());
    }

    #[Test]
    public function it_locks_every_parent_before_any_entity_in_one_sequence(): void
    {
        $parents = [$this->parentNote(7), $this->parentNote(8), $this->parentNote(9)];
        $low = $this->noteWithParentLock(1, parent: $parents[2]);
        $middle = $this->noteWithParentLock(2, parent: $parents[0]);
        $high = $this->noteWithParentLock(3, parent: $parents[1]);
        $locked = $this->recordLocks();

        EntityTarget::for($middle, ApiVersion::V1)->alsoLocking($high, $low)->lock();

        $this->assertSame([...$parents, $low->getKey(), $middle->getKey(), $high->getKey()], $locked->pluck('key')->all());
    }

    #[Test]
    public function it_locks_a_parent_that_several_models_share_once(): void
    {
        $parent = $this->parentNote(9);
        $low = $this->noteWithParentLock(1, parent: $parent);
        $high = $this->noteWithParentLock(2, parent: $parent);
        $locked = $this->recordLocks();

        EntityTarget::for($high, ApiVersion::V1)->alsoLocking($low)->lock();

        $this->assertSame([$parent, $low->getKey(), $high->getKey()], $locked->pluck('key')->all());
    }

    #[Test]
    public function it_refuses_a_model_whose_parent_changed_before_its_lock(): void
    {
        $source = $this->parentNote(8);
        $destination = $this->parentNote(9);
        $note = $this->noteWithParentLock(1, parent: $source);

        DB::table('notes')->where('id', $note->getKey())->update(['parent_id' => $destination]);

        $this->expectException(ParentsChanged::class);

        EntityTarget::for($note, ApiVersion::V1)->lock();
    }

    #[Test]
    public function it_locks_the_parents_of_a_move_in_one_order_before_the_item(): void
    {
        $destination = Note::query()->findOrFail($this->parentNote(8));
        $source = $this->parentNote(9);
        $item = $this->noteWithParentLock(3, parent: $source);
        $locked = $this->recordLocks();

        EntityTarget::for($item, ApiVersion::V1)->alsoLocking($destination)->lock();

        $this->assertSame([$destination->getKey(), $source, $item->getKey()], $locked->pluck('key')->all());
    }

    #[Test]
    public function it_refuses_models_that_swapped_parents_before_their_locks(): void
    {
        $first = $this->parentNote(8);
        $second = $this->parentNote(9);
        $low = $this->noteWithParentLock(1, parent: $first);
        $high = $this->noteWithParentLock(2, parent: $second);

        DB::table('notes')->where('id', $low->getKey())->update(['parent_id' => $second]);
        DB::table('notes')->where('id', $high->getKey())->update(['parent_id' => $first]);

        $this->expectException(ParentsChanged::class);

        EntityTarget::for($low, ApiVersion::V1)->alsoLocking($high)->lock();
    }

    #[Test]
    public function it_refuses_a_parent_on_another_connection(): void
    {
        $note = (new NoteWithParentLock(['title' => 'Child', 'parent_id' => $this->key(9)]))->setConnection('secondary');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('cannot also lock [%s]', Note::class));

        EntityTarget::for($note, ApiVersion::V1)->lock();
    }

    #[Test]
    public function it_locks_only_the_rows_of_models_that_lock_no_parents(): void
    {
        $parent = Note::query()->create(['title' => 'Parent']);
        $note = (new Note(['title' => 'Child', 'parent_id' => $parent->getKey()]))->forceFill(['id' => $this->key(1)]);
        $note->save();
        $other = (new Note(['title' => 'Other']))->forceFill(['id' => $this->key(2)]);
        $other->save();
        $locked = $this->recordLocks();

        EntityTarget::for($other, ApiVersion::V1)->alsoLocking($note)->lock();

        $sql = 'select * from "notes" where "notes"."id" = ? and "notes"."deleted_at" is null limit 1';

        $this->assertSame([['sql' => $sql, 'key' => $this->key(1)], ['sql' => $sql, 'key' => $this->key(2)]], $locked->all());
    }

    /**
     * @return Collection<int, array{sql: string, key: mixed}>
     */
    private function recordLocks(): Collection
    {
        $locked = new Collection;

        Event::listen(function (QueryExecuted $query) use ($locked): void {
            $locked->push(['sql' => $query->sql, 'key' => $query->bindings[0] ?? null]);
        });

        return $locked;
    }

    private function key(int $number): string
    {
        return sprintf('00000000-0000-4000-8000-%012d', $number);
    }

    private function parentNote(int $number): string
    {
        $parent = (new Note(['title' => 'Parent']))->forceFill(['id' => $this->key($number)]);
        $parent->save();

        return $this->key($number);
    }

    private function noteWithParentLock(int $number, string $parent): NoteWithParentLock
    {
        $note = (new NoteWithParentLock(['title' => 'Child', 'parent_id' => $parent]))->forceFill(['id' => $this->key($number)]);
        $note->save();

        return $note;
    }

    private function partialNote(): Note
    {
        $key = Note::query()->create(['title' => 'Draft'])->getKey();

        return Note::query()->findOrFail($key, ['id', 'title', ConcurrencyRevision::COLUMN]);
    }

    private function note(int $self, int $childrenOrder): Note
    {
        $note = Note::query()->create(['title' => 'Draft']);

        DB::table('notes')->where('id', $note->getKey())->update([
            ConcurrencyRevision::COLUMN => $self,
            ConcurrencyRevision::namedColumn(NoteRevision::ChildrenOrder->value) => $childrenOrder,
        ]);

        return $note->refresh();
    }
}
