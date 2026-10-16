<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\Fixtures\Support\Http\Api\Preconditions\Note;
use Tests\Fixtures\Support\Http\Api\Preconditions\Precondition as NotePrecondition;
use Tests\TestCase;

#[CoversClass(Precondition::class)]
final class PreconditionTest extends TestCase
{
    use WithWorkbench;

    private const string STALE = '"0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f"';

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('notes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->nullable();
            $table->string('title');
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
    public function it_runs_the_write_when_if_match_names_the_current_tag(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $tag = $this->etag($note);

        $response = $this->withHeader('If-Match', $tag)->patchJson($this->uri($note), ['title' => 'Final'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Final');

        $this->assertSame('Final', $note->refresh()->title);
        $this->assertNotSame($tag, $response->headers->get('ETag'));
        $response->assertHeader('ETag', $this->etag($note));
    }

    #[Test]
    public function it_runs_the_write_when_if_match_is_a_wildcard(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->withHeader('If-Match', '*')->patchJson($this->uri($note), ['title' => 'Final'])->assertOk();

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
    public function it_returns_412_with_the_current_etag_for_a_stale_tag(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->assertPreconditionFailed(
            $this->withHeader('If-Match', self::STALE)->patchJson($this->uri($note), ['title' => 'Final'])
        )->assertHeader('ETag', $this->etag($note));

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
            $this->withHeader('If-Match', '')->patchJson($this->uri($note), ['title' => 'Final'])
        );
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
    public function it_runs_the_write_where_the_precondition_is_required_and_if_match_names_the_current_tag(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->withHeader('If-Match', $this->etag($note))->patchJson($this->uri($note, 'required'), ['title' => 'Final'])->assertOk();

        $this->assertSame('Final', $note->refresh()->title);
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
    public function it_returns_412_before_422(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->assertPreconditionFailed($this->withHeader('If-Match', self::STALE)->patchJson($this->uri($note)));
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
    public function it_returns_422_after_a_matching_tag(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->withHeader('If-Match', $this->etag($note))->patchJson($this->uri($note))
            ->assertUnprocessable()
            ->assertJsonPath('errors.*.code', ['validation.required']);
    }

    #[Test]
    public function it_returns_404_before_412(): void
    {
        $this->withHeader('If-Match', self::STALE)->patchJson('api/preconditions/notes/'.Str::uuid(), ['title' => 'Final'])
            ->assertNotFound()
            ->assertJsonPath('errors.*.code', ['not_found.resource']);
    }

    #[Test]
    public function it_returns_412_when_a_concurrent_write_lands_before_the_guard(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $tag = $this->etag($note);

        $this->app->afterResolving(NotePrecondition::class, function () use ($note): void {
            Note::query()->whereKey($note->getKey())->update(['title' => 'Written by another request']);
        });

        $response = $this->assertPreconditionFailed(
            $this->withHeader('If-Match', $tag)->patchJson($this->uri($note), ['title' => 'Final'])
        );

        $this->assertSame('Written by another request', $note->refresh()->title);
        $this->assertNotSame($tag, $response->headers->get('ETag'));
        $response->assertHeader('ETag', $this->etag($note));
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
        return $response->assertStatus(Response::HTTP_PRECONDITION_FAILED)->assertExactJson([
            'errors' => [
                ['code' => 'precondition.failed', 'detail' => 'The If-Match precondition does not match the current resource.', 'source' => ['header' => 'If-Match']],
            ],
            'meta' => [],
        ]);
    }
}
