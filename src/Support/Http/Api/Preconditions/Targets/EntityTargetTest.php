<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions\Targets;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Database\Eloquent\Concurrency\ConcurrencyRevision;
use Support\Database\Eloquent\Concurrency\Exceptions\UndeclaredConcurrencyRevision;
use Tests\Fixtures\Support\Http\Api\Preconditions\Note;
use Tests\Fixtures\Support\Http\Api\Preconditions\NoteRevision;
use Tests\Fixtures\Support\Http\Api\Preconditions\SecondaryNote;
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

        $this->assertSame(['self' => 3], EntityTarget::for($note, ApiVersion::V1)->comparingSelf()->compared());
    }

    #[Test]
    public function it_compares_only_the_named_revisions(): void
    {
        $note = $this->note(self: 3, childrenOrder: 5);

        $this->assertSame(['children_order' => 5], EntityTarget::for($note, ApiVersion::V1)->comparingNamed(NoteRevision::ChildrenOrder)->compared());
    }

    #[Test]
    public function it_adds_each_comparison_to_the_set(): void
    {
        $note = $this->note(self: 3, childrenOrder: 5);

        $this->assertSame(
            ['self' => 3, 'children_order' => 5],
            EntityTarget::for($note, ApiVersion::V1)->comparingSelf()->comparingNamed(NoteRevision::ChildrenOrder)->compared(),
        );
    }

    #[Test]
    public function it_compares_every_declared_revision(): void
    {
        $note = $this->note(self: 3, childrenOrder: 5);

        $this->assertSame(['self' => 3, 'children_order' => 5], EntityTarget::for($note, ApiVersion::V1)->comparingAll()->compared());
    }

    #[Test]
    public function it_refuses_a_revision_the_model_does_not_declare(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->expectException(UndeclaredConcurrencyRevision::class);

        EntityTarget::for($note, ApiVersion::V1)->comparingNamed(UndeclaredRevision::Attachments);
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

        $this->assertSame(['self' => 1, 'children_order' => 1], $target->compared());

        $target->lock();

        $this->assertSame(['self' => 2, 'children_order' => 1], $target->compared());
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
