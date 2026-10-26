<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Database\Eloquent\Concurrency\ConcurrencyRevision;
use Support\Http\Resources\Schemas\Tags\EntityTag;
use Symfony\Component\HttpFoundation\Response;
use Tests\Fixtures\Support\Http\Api\Preconditions\Note;
use Tests\Fixtures\Support\Http\Api\Preconditions\NoteRevision;
use Tests\Fixtures\Support\Http\Api\Preconditions\Precondition as NotePrecondition;
use Tests\Fixtures\Support\Http\Api\Preconditions\SecondaryNote;
use Tests\Fixtures\Support\Schemas\ApiVersion;
use Tests\TestCase;

#[CoversClass(Precondition::class)]
#[WithConfig('database.connections.secondary', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''])]
final class PreconditionTest extends TestCase
{
    use WithWorkbench;

    private const string STALE = '"v1.999.0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f"';

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

        Schema::connection('secondary')->create('secondary_notes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->concurrencyRevision();
            $table->timestampsTz();
        });
    }

    #[Test]
    public function it_runs_the_write_without_if_match_where_the_precondition_is_optional(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->patchJson($this->uri($note), ['title' => 'Final'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Final');

        $this->assertSame('Final', $note->refresh()->title);
    }

    #[Test]
    public function it_validates_a_request_without_if_match_where_the_precondition_is_optional(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->patchJson($this->uri($note))
            ->assertUnprocessable()
            ->assertJsonPath('errors.*.code', ['validation.required']);
    }

    #[Test]
    public function it_returns_428_without_if_match_where_the_precondition_is_required(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->patchJson($this->uri($note, 'required'), ['title' => 'Final'])
            ->assertStatus(Response::HTTP_PRECONDITION_REQUIRED)
            ->assertExactJson([
                'errors' => [
                    ['code' => 'precondition.required', 'detail' => 'This operation requires the If-Match header.', 'source' => ['header' => 'If-Match']],
                ],
                'meta' => [],
            ]);

        $this->assertSame('Draft', $note->refresh()->title);
    }

    #[Test]
    public function it_runs_the_write_when_if_match_names_the_current_tag(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->withHeader('If-Match', $this->etag($note))->patchJson($this->uri($note), ['title' => 'Final'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Final');

        $this->assertSame('Final', $note->refresh()->title);
    }

    #[Test]
    public function it_runs_the_write_where_the_precondition_is_required_and_if_match_names_the_current_tag(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->withHeader('If-Match', $this->etag($note))->patchJson($this->uri($note, 'required'), ['title' => 'Final'])->assertOk();

        $this->assertSame('Final', $note->refresh()->title);
    }

    #[Test]
    public function it_ignores_the_digest_when_it_compares(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $tag = preg_replace('/\.[0-9a-f]{32}"$/', '.'.str_repeat('0', 32).'"', $this->etag($note));

        $this->withHeader('If-Match', (string) $tag)->patchJson($this->uri($note), ['title' => 'Final'])->assertOk();

        $this->assertSame('Final', $note->refresh()->title);
    }

    #[Test]
    public function it_runs_the_write_when_if_match_is_a_wildcard(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->withHeader('If-Match', '*')->patchJson($this->uri($note, 'required'), ['title' => 'Final'])->assertOk();

        $this->assertSame('Final', $note->refresh()->title);
    }

    #[Test]
    public function it_runs_the_write_when_every_if_match_line_is_a_wildcard(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->withHeaders(['If-Match' => ['*', '*']])->patchJson($this->uri($note), ['title' => 'Final'])->assertOk();

        $this->assertSame('Final', $note->refresh()->title);
    }

    #[Test]
    public function it_matches_the_current_tag_anywhere_in_a_list(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->withHeader('If-Match', self::STALE.', '.$this->etag($note))
            ->patchJson($this->uri($note), ['title' => 'Final'])
            ->assertOk();

        $this->assertSame('Final', $note->refresh()->title);
    }

    #[Test]
    public function it_matches_the_current_tag_on_any_if_match_line(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->withHeaders(['If-Match' => [self::STALE, $this->etag($note)]])
            ->patchJson($this->uri($note), ['title' => 'Final'])
            ->assertOk();

        $this->assertSame('Final', $note->refresh()->title);
    }

    #[Test]
    public function it_returns_412_when_no_tag_in_a_list_matches(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->assertPreconditionFailed(
            $this->withHeader('If-Match', self::STALE.', "v1.998.0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f"')->patchJson($this->uri($note), ['title' => 'Final'])
        );

        $this->assertSame('Draft', $note->refresh()->title);
    }

    #[Test]
    public function it_returns_412_for_a_weak_tag(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->assertPreconditionFailed(
            $this->withHeader('If-Match', 'W/'.$this->etag($note))->patchJson($this->uri($note), ['title' => 'Final'])
        );

        $this->assertSame('Draft', $note->refresh()->title);
    }

    #[Test]
    public function it_returns_412_for_an_empty_if_match(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->assertPreconditionFailed(
            $this->withHeader('If-Match', '')->patchJson($this->uri($note, 'required'), ['title' => 'Final'])
        );
    }

    #[Test]
    public function it_returns_412_for_a_stale_own_revision(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $tag = $this->etag($note);

        $this->revise($note, ConcurrencyRevision::COLUMN, 2);

        $this->assertPreconditionFailed(
            $this->withHeader('If-Match', $tag)->patchJson($this->uri($note), ['title' => 'Final'])
        );

        $this->assertSame('Draft', $note->refresh()->title);
    }

    #[Test]
    public function it_returns_412_for_another_api_version(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $tag = Str::replaceFirst('"v1.', '"v2.', $this->etag($note));

        $this->assertPreconditionFailed(
            $this->withHeader('If-Match', $tag)->patchJson($this->uri($note), ['title' => 'Final'])
        );

        $this->assertSame('Draft', $note->refresh()->title);
    }

    #[Test]
    public function it_compares_only_the_named_revision_where_the_target_compares_a_named_revision(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $tag = $this->etag($note);

        $this->revise($note, ConcurrencyRevision::COLUMN, 2);

        $this->withHeader('If-Match', $tag)->patchJson($this->uri($note, 'children-order'), ['title' => 'Final'])
            ->assertNoContent();

        $this->assertSame('Final', $note->refresh()->title);
    }

    #[Test]
    public function it_matches_a_nested_index_tag_where_the_target_compares_its_named_revision(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->withHeader('If-Match', '"v1.children_order=1"')->patchJson($this->uri($note, 'children-order'), ['title' => 'Final'])
            ->assertNoContent();

        $this->assertSame('Final', $note->refresh()->title);
    }

    #[Test]
    public function it_answers_a_reorder_with_the_nested_index_tag_that_reflects_the_write(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $sent = '"v1.children_order=1"';

        $response = $this->withHeader('If-Match', $sent)->patchJson($this->uri($note, 'children-order'), ['title' => 'Final'])
            ->assertNoContent();

        $current = (string) EntityTag::forNamedRevision($note->refresh(), NoteRevision::ChildrenOrder, ApiVersion::V1);

        $this->assertNotSame($sent, $current);
        $response->assertHeader('ETag', $current);
    }

    #[Test]
    public function it_runs_the_next_reorder_with_the_tag_of_the_previous_answer(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $tag = $this->withHeader('If-Match', '"v1.children_order=1"')->patchJson($this->uri($note, 'children-order'), ['title' => 'First'])
            ->assertNoContent()
            ->headers->get('ETag');

        $this->assertIsString($tag);

        $this->withHeader('If-Match', $tag)->patchJson($this->uri($note, 'children-order'), ['title' => 'Second'])
            ->assertNoContent();

        $this->assertSame('Second', $note->refresh()->title);
    }

    #[Test]
    public function it_returns_412_for_a_stale_named_revision(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $tag = $this->etag($note);

        $this->revise($note, ConcurrencyRevision::namedColumn(NoteRevision::ChildrenOrder->value), 2);

        $this->assertPreconditionFailed(
            $this->withHeader('If-Match', $tag)->patchJson($this->uri($note, 'children-order'), ['title' => 'Final'])
        );

        $this->assertSame('Draft', $note->refresh()->title);
    }

    #[Test]
    public function it_runs_the_write_where_the_target_compares_every_revision_and_all_match(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->withHeader('If-Match', $this->etag($note))->deleteJson($this->uri($note))->assertNoContent();

        $this->assertSoftDeleted($note);
    }

    #[Test]
    public function it_returns_412_where_the_target_compares_every_revision_and_the_own_revision_is_stale(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $tag = $this->etag($note);

        $this->revise($note, ConcurrencyRevision::COLUMN, 2);

        $this->assertPreconditionFailed($this->withHeader('If-Match', $tag)->deleteJson($this->uri($note)));

        $this->assertNotSoftDeleted($note);
    }

    #[Test]
    public function it_returns_412_where_the_target_compares_every_revision_and_a_named_revision_is_stale(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $tag = $this->etag($note);

        $this->revise($note, ConcurrencyRevision::namedColumn(NoteRevision::ChildrenOrder->value), 2);

        $this->assertPreconditionFailed($this->withHeader('If-Match', $tag)->deleteJson($this->uri($note)));

        $this->assertNotSoftDeleted($note);
    }

    #[Test]
    public function it_returns_404_before_403(): void
    {
        $this->patchJson('api/preconditions/notes/'.Str::uuid().'/forbidden', ['title' => 'Final'])
            ->assertNotFound()
            ->assertJsonPath('errors.*.code', ['not_found.resource']);
    }

    #[Test]
    public function it_returns_404_before_412(): void
    {
        $this->withHeader('If-Match', self::STALE)->patchJson('api/preconditions/notes/'.Str::uuid(), ['title' => 'Final'])
            ->assertNotFound()
            ->assertJsonPath('errors.*.code', ['not_found.resource']);
    }

    #[Test]
    public function it_returns_403_before_428(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->patchJson($this->uri($note, 'forbidden'), ['title' => 'Final'])
            ->assertForbidden()
            ->assertJsonPath('errors.*.code', ['permission.denied']);
    }

    #[Test]
    public function it_returns_403_before_412(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->withHeader('If-Match', self::STALE)->patchJson($this->uri($note, 'forbidden'), ['title' => 'Final'])
            ->assertForbidden()
            ->assertJsonPath('errors.*.code', ['permission.denied']);
    }

    #[Test]
    public function it_returns_428_before_422(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->patchJson($this->uri($note, 'required'))
            ->assertStatus(Response::HTTP_PRECONDITION_REQUIRED)
            ->assertJsonPath('errors.*.code', ['precondition.required']);
    }

    #[Test]
    public function it_returns_412_before_422(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->assertPreconditionFailed($this->withHeader('If-Match', self::STALE)->patchJson($this->uri($note)));
    }

    #[Test]
    public function it_returns_422_after_a_matching_tag(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->withHeader('If-Match', $this->etag($note))->patchJson($this->uri($note))
            ->assertUnprocessable()
            ->assertJsonPath('errors.*.code', ['validation.required']);
    }

    #[Test]
    public function it_returns_412_when_a_concurrent_write_lands_before_the_guard(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $tag = $this->etag($note);

        $this->app->afterResolving(NotePrecondition::class, function () use ($note): void {
            DB::table('notes')->where('id', $note->getKey())->update(['title' => 'Written by another request', ConcurrencyRevision::COLUMN => 2]);
        });

        $this->assertPreconditionFailed(
            $this->withHeader('If-Match', $tag)->patchJson($this->uri($note), ['title' => 'Final'])
        );

        $this->assertSame('Written by another request', $note->refresh()->title);
    }

    #[Test]
    public function it_returns_404_when_a_concurrent_delete_lands_before_the_guard(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->app->afterResolving(NotePrecondition::class, function () use ($note): void {
            Note::query()->whereKey($note->getKey())->forceDelete();
        });

        $this->withHeader('If-Match', '*')->patchJson($this->uri($note), ['title' => 'Final'])
            ->assertNotFound()
            ->assertJsonPath('errors.*.code', ['not_found.resource']);
    }

    #[Test]
    public function it_returns_404_when_a_concurrent_soft_delete_lands_before_the_guard(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->app->afterResolving(NotePrecondition::class, function () use ($note): void {
            Note::query()->whereKey($note->getKey())->delete();
        });

        $this->withHeader('If-Match', '*')->patchJson($this->uri($note), ['title' => 'Final'])
            ->assertNotFound()
            ->assertJsonPath('errors.*.code', ['not_found.resource']);

        $this->assertSame('Draft', Note::withTrashed()->findOrFail($note->getKey())->title);
    }

    #[Test]
    public function it_runs_the_write_on_a_trashed_entity_where_the_route_binds_trashed_entities(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $note->delete();

        $this->withHeader('If-Match', '*')->patchJson($this->uri($note, 'trashed'), ['title' => 'Final'])->assertOk();

        $this->assertSame('Final', Note::withTrashed()->findOrFail($note->getKey())->title);
    }

    #[Test]
    public function it_locks_inside_a_transaction_on_the_connection_of_the_target(): void
    {
        $note = SecondaryNote::query()->create(['title' => 'Draft']);
        $statements = $this->recordStatements('secondary');

        $this->withHeader('If-Match', '*')->patchJson("api/preconditions/secondary-notes/{$note->getKey()}", ['title' => 'Final'])
            ->assertOk();

        $this->assertSame(['select at 0', 'begin', 'select at 1', 'update at 1', 'select at 1', 'commit'], $statements->all());
        $this->assertSame('Final', $note->refresh()->title);
    }

    #[Test]
    public function it_runs_the_write_in_the_transaction_that_holds_the_lock(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $statements = $this->recordStatements(DB::getDefaultConnection());
        $level = DB::transactionLevel();
        $inner = $level + 1;

        $this->withHeader('If-Match', '*')->patchJson($this->uri($note), ['title' => 'Final'])->assertOk();

        $this->assertSame(["select at {$level}", 'begin', "select at {$inner}", "update at {$inner}", "select at {$inner}", 'commit'], $statements->all());
    }

    /**
     * @return Collection<int, string>
     */
    private function recordStatements(string $connection): Collection
    {
        $statements = new Collection;

        Event::listen(function (QueryExecuted $query) use ($connection, $statements): void {
            if ($query->connectionName === $connection) {
                $statements->push(Str::before($query->sql, ' ').' at '.$query->connection->transactionLevel());
            }
        });
        Event::listen(function (TransactionBeginning $event) use ($connection, $statements): void {
            if ($event->connectionName === $connection) {
                $statements->push('begin');
            }
        });
        Event::listen(function (TransactionCommitted $event) use ($connection, $statements): void {
            if ($event->connectionName === $connection) {
                $statements->push('commit');
            }
        });

        return $statements;
    }

    private function revise(Note $note, string $column, int $revision): void
    {
        DB::table('notes')->where('id', $note->getKey())->update([$column => $revision]);
    }

    private function uri(Note $note, null|string $suffix = null): string
    {
        return collect(['api/preconditions/notes', $note->getKey(), $suffix])->filter()->implode('/');
    }

    private function etag(Note $note): string
    {
        $etag = $this->getJson($this->uri($note))->assertOk()->headers->get('ETag');

        $this->assertIsString($etag);

        return $etag;
    }

    /**
     * @param  TestResponse<Response>  $response
     * @return TestResponse<Response>
     */
    private function assertPreconditionFailed(TestResponse $response): TestResponse
    {
        return $response->assertStatus(Response::HTTP_PRECONDITION_FAILED)->assertHeaderMissing('ETag')->assertExactJson([
            'errors' => [
                ['code' => 'precondition.failed', 'detail' => 'The If-Match precondition does not match the current resource.', 'source' => ['header' => 'If-Match']],
            ],
            'meta' => [],
        ]);
    }
}
