<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions\Targets;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Http\Api\Preconditions\Note;
use Tests\Fixtures\Support\Schemas\ApiVersion;
use Tests\TestCase;

#[CoversClass(EntityTarget::class)]
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
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    #[Test]
    public function it_tags_the_entity_with_the_etag_of_its_schema_response(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->getJson("api/preconditions/notes/{$note->getKey()}")
            ->assertOk()
            ->assertHeader('ETag', (string) new EntityTarget($note, ApiVersion::V1)->tag());
    }

    #[Test]
    public function it_loads_the_committed_row_when_it_locks(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $target = new EntityTarget($note, ApiVersion::V1);
        $before = (string) $target->tag();

        Note::query()->whereKey($note->getKey())->update(['title' => 'Final']);

        $target->lock();

        $this->assertSame('Final', $note->title);
        $this->assertFalse($note->isDirty());
        $this->assertNotSame($before, (string) $target->tag());
    }

    #[Test]
    public function it_keeps_a_constrained_relation_when_it_locks(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        Note::query()->create(['title' => 'Public', 'parent_id' => $note->getKey()]);
        Note::query()->create(['title' => 'Private', 'parent_id' => $note->getKey()]);
        $note->load(['children' => fn ($query) => $query->where('title', 'Public')]);

        new EntityTarget($note, ApiVersion::V1)->lock();

        $this->assertSame(['Public'], $note->children->pluck('title')->all());
    }

    #[Test]
    public function it_keeps_a_nested_relation_when_it_locks(): void
    {
        $grandparent = Note::query()->create(['title' => 'Grandparent']);
        $parent = Note::query()->create(['title' => 'Parent', 'parent_id' => $grandparent->getKey()]);
        $note = Note::query()->create(['title' => 'Child', 'parent_id' => $parent->getKey()])->load('parent.parent');

        new EntityTarget($note, ApiVersion::V1)->lock();

        $this->assertTrue($note->parent?->relationLoaded('parent'));
        $this->assertSame('Grandparent', $note->parent->parent?->title);
    }

    #[Test]
    public function it_fails_to_lock_a_row_that_no_longer_exists(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        Note::query()->whereKey($note->getKey())->forceDelete();

        $this->expectException(ModelNotFoundException::class);

        new EntityTarget($note, ApiVersion::V1)->lock();
    }

    #[Test]
    public function it_selects_the_row_for_update_when_it_locks(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $connection = DB::connection();
        $connection->setQueryGrammar(new PostgresGrammar($connection));

        $queries = $connection->pretend(function () use ($note): void {
            rescue(fn () => new EntityTarget($note, ApiVersion::V1)->lock(), report: false);
        });

        $this->assertSame("select * from \"notes\" where \"notes\".\"id\" = '{$note->getKey()}' and \"notes\".\"deleted_at\" is null limit 1 for update", $queries[0]['query']);
    }
}
