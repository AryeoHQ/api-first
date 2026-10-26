<?php

declare(strict_types=1);

namespace Support\Http\Api\Middleware;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\HttpFoundation\Response;
use Tests\Fixtures\Support\Http\Api\Preconditions\Note;
use Tests\Fixtures\Support\Http\Api\Preconditions\NoteRevision;
use Tests\TestCase;

#[CoversClass(ConditionalGet::class)]
final class ConditionalGetTest extends TestCase
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
    public function it_answers_a_matching_tag_with_not_modified_and_the_tag(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $this->notModified($this->getJson($this->uri($note), ['If-None-Match' => $etag]), $etag);
    }

    #[Test]
    public function it_answers_a_head_request_with_a_matching_tag_with_not_modified(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $this->notModified($this->call('HEAD', $this->uri($note), server: ['HTTP_IF_NONE_MATCH' => $etag]), $etag);
    }

    #[Test]
    public function it_answers_a_mismatched_tag_with_the_representation(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $this->getJson($this->uri($note), ['If-None-Match' => '"v1.999.0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f"'])
            ->assertOk()
            ->assertHeader('ETag', $etag)
            ->assertJsonPath('data.title', 'Draft');
    }

    #[Test]
    public function it_answers_a_tag_whose_digest_differs_with_the_representation(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);
        $stale = substr($etag, 0, -3).'0"';

        $this->assertNotSame($etag, $stale);

        $this->getJson($this->uri($note), ['If-None-Match' => $stale])->assertOk();
    }

    #[Test]
    public function it_answers_the_representation_after_the_resource_changes(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $note->update(['title' => 'Final']);

        $this->getJson($this->uri($note), ['If-None-Match' => $etag])
            ->assertOk()
            ->assertJsonPath('data.title', 'Final');
    }

    #[Test]
    public function it_answers_a_request_without_if_none_match_with_the_representation(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->getJson($this->uri($note))->assertOk()->assertHeader('ETag')->assertJsonPath('data.title', 'Draft');
    }

    #[Test]
    public function it_answers_an_empty_if_none_match_with_the_representation(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->getJson($this->uri($note), ['If-None-Match' => ''])->assertOk();
    }

    #[Test]
    public function it_matches_a_weak_request_tag(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $this->notModified($this->getJson($this->uri($note), ['If-None-Match' => 'W/'.$etag]), $etag);
    }

    #[Test]
    public function it_matches_a_weak_response_tag(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $this->notModified($this->getJson($this->uri($note, 'weak'), ['If-None-Match' => $etag]), 'W/'.$etag);
        $this->notModified($this->getJson($this->uri($note, 'weak'), ['If-None-Match' => 'W/'.$etag]), 'W/'.$etag);
    }

    #[Test]
    public function it_matches_a_star(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->notModified($this->getJson($this->uri($note), ['If-None-Match' => '*']), $this->etag($note));
    }

    #[Test]
    public function it_matches_a_list_in_which_one_tag_matches(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $this->notModified(
            $this->getJson($this->uri($note), ['If-None-Match' => '"v1.999.0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f", W/'.$etag.' ,"other"']),
            $etag,
        );
    }

    #[Test]
    public function it_answers_a_list_in_which_no_tag_matches_with_the_representation(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->getJson($this->uri($note), ['If-None-Match' => '"one", W/"two"'])->assertOk();
    }

    #[Test]
    public function it_leaves_a_list_response_untouched(): void
    {
        Note::query()->create(['title' => 'Draft']);

        $this->getJson('api/conditional-get/notes', ['If-None-Match' => '*'])
            ->assertOk()
            ->assertHeaderMissing('ETag')
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function it_leaves_a_tagged_nested_index_untouched_after_an_item_changes(): void
    {
        $parent = Note::query()->create(['title' => 'Parent']);
        $child = Note::query()->create(['title' => 'Draft', 'parent_id' => $parent->getKey()]);
        $uri = $this->uri($parent, 'children');
        $etag = $this->getJson($uri)->assertOk()->headers->get('ETag');

        $this->assertSame('"v1.children_order=1"', $etag);

        $child->update(['title' => 'Renamed']);

        foreach ([$etag, 'W/'.$etag, '*'] as $ifNoneMatch) {
            $this->getJson($uri, ['If-None-Match' => $ifNoneMatch])
                ->assertOk()
                ->assertHeader('ETag', $etag)
                ->assertJsonPath('data.0.title', 'Renamed');
        }
    }

    #[Test]
    public function it_matches_a_tag_on_a_later_if_none_match_line(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $this->notModified($this->withHeaders(['If-None-Match' => ['"stale"', $etag]])->getJson($this->uri($note)), $etag);
    }

    #[Test]
    public function it_answers_a_star_beside_a_tag_on_another_line_with_the_representation(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $this->withHeaders(['If-None-Match' => ['"stale"', '*']])->getJson($this->uri($note))
            ->assertOk()
            ->assertHeader('ETag', $etag)
            ->assertJsonPath('data.title', 'Draft');
    }

    #[Test]
    public function it_answers_a_star_on_every_line_with_the_representation(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);

        $this->withHeaders(['If-None-Match' => ['*', '*']])->getJson($this->uri($note))->assertOk()->assertJsonPath('data.title', 'Draft');
    }

    #[Test]
    public function it_ignores_empty_list_elements(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $this->notModified($this->getJson($this->uri($note), ['If-None-Match' => ', "stale",, '.$etag.' ,']), $etag);
    }

    #[Test]
    #[TestWith(['garbage %s'])]
    #[TestWith(['%s garbage'])]
    #[TestWith(['%s, garbage'])]
    #[TestWith(['"stale" %s'])]
    #[TestWith(['x/%s'])]
    #[TestWith(['w/%s'])]
    #[TestWith(['* %s'])]
    public function it_answers_a_malformed_if_none_match_with_the_representation(string $format): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $this->getJson($this->uri($note), ['If-None-Match' => sprintf($format, $etag)])
            ->assertOk()
            ->assertHeader('ETag', $etag)
            ->assertJsonPath('data.title', 'Draft');
    }

    #[Test]
    public function it_answers_a_malformed_later_if_none_match_line_with_the_representation(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $this->withHeaders(['If-None-Match' => [$etag, 'garbage']])->getJson($this->uri($note))->assertOk();
    }

    #[Test]
    public function it_leaves_a_write_untouched(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        foreach (['postJson', 'putJson'] as $method) {
            $this->{$method}($this->uri($note), [], ['If-None-Match' => $etag])
                ->assertOk()
                ->assertHeader('ETag', $etag)
                ->assertJsonPath('data.title', 'Draft');
        }
    }

    #[Test]
    public function it_leaves_a_response_that_is_not_ok_untouched(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $this->getJson($this->uri($note, 'created'), ['If-None-Match' => $etag])
            ->assertCreated()
            ->assertHeader('ETag', $etag)
            ->assertJsonPath('data.title', 'Draft');
    }

    #[Test]
    public function it_leaves_a_response_without_a_tag_untouched(): void
    {
        $this->getJson('api/conditional-get/notes', ['If-None-Match' => '"anything"'])->assertOk()->assertHeaderMissing('ETag');
    }

    #[Test]
    public function it_leaves_a_route_outside_the_configured_middleware_groups_untouched(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $this->getJson('conditional-get/outside/notes/'.$note->getKey(), ['If-None-Match' => $etag])
            ->assertOk()
            ->assertHeader('ETag', $etag)
            ->assertJsonPath('data.title', 'Draft');
    }

    #[Test]
    #[WithConfig('api-first.errors.middleware_groups', [])]
    public function it_answers_not_modified_on_a_group_that_the_error_renderer_does_not_serve(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $this->notModified($this->getJson($this->uri($note), ['If-None-Match' => $etag]), $etag);
    }

    #[Test]
    #[WithConfig('api-first.conditional_get.middleware_groups', [])]
    public function it_reads_its_middleware_groups_from_its_own_config_key(): void
    {
        $note = Note::query()->create(['title' => 'Draft']);
        $etag = $this->etag($note);

        $this->getJson($this->uri($note), ['If-None-Match' => $etag])
            ->assertOk()
            ->assertHeader('ETag', $etag)
            ->assertJsonPath('data.title', 'Draft');
    }

    /**
     * @param  TestResponse<Response>  $response
     */
    private function notModified(TestResponse $response, string $etag): void
    {
        $response->assertNoContent(Response::HTTP_NOT_MODIFIED)->assertHeader('ETag', $etag)->assertHeaderMissing('Content-Length')->assertHeaderMissing('Content-Type');
    }

    private function uri(Note $note, null|string $suffix = null): string
    {
        return collect(['api/conditional-get/notes', $note->getKey(), $suffix])->filter()->implode('/');
    }

    private function etag(Note $note): string
    {
        $etag = $this->getJson($this->uri($note))->assertOk()->headers->get('ETag');

        $this->assertIsString($etag);

        return $etag;
    }
}
