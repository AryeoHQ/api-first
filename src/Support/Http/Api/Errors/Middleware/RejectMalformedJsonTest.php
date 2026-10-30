<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors\Middleware;

use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

#[CoversClass(RejectMalformedJson::class)]
final class RejectMalformedJsonTest extends TestCase
{
    use WithWorkbench;

    #[Test]
    public function it_rejects_a_malformed_json_body_on_a_lenient_route_as_400(): void
    {
        $this->call('POST', 'api/errors/rules', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], content: '{"name":')
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertExactJson(['errors' => [['code' => 'error', 'detail' => 'The API cannot parse the request.']], 'meta' => []]);
    }

    #[Test]
    public function it_rejects_a_malformed_json_body_before_a_legacy_controller_runs(): void
    {
        $this->call('POST', 'api/errors/legacy/validate', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], content: '{"title":')
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertExactJson(['errors' => [['code' => 'error', 'detail' => 'The API cannot parse the request.']], 'meta' => []]);
    }

    #[Test]
    public function it_rejects_a_malformed_json_body_on_a_covered_path_outside_every_middleware_group(): void
    {
        config()->set('api-first.errors.paths', ['errors/outside/*']);

        $this->call('POST', 'errors/outside/validate', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], content: '{"title":')
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertExactJson(['errors' => [['code' => 'error', 'detail' => 'The API cannot parse the request.']], 'meta' => []]);
    }

    #[Test]
    #[TestWith(['application/json; charset=utf-8'])]
    #[TestWith(['application/problem+json'])]
    public function it_rejects_a_malformed_body_of_a_json_media_type_with_parameters_or_a_suffix(string $type): void
    {
        $this->call('POST', 'api/errors/rules', server: ['CONTENT_TYPE' => $type, 'HTTP_ACCEPT' => 'application/json'], content: '{"name":')
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertExactJson(['errors' => [['code' => 'error', 'detail' => 'The API cannot parse the request.']], 'meta' => []]);
    }

    #[Test]
    public function it_passes_a_body_of_a_media_type_that_only_contains_json_to_the_route(): void
    {
        $this->call('POST', 'api/errors/rules', server: ['CONTENT_TYPE' => 'application/jsonl', 'HTTP_ACCEPT' => 'application/json'], content: "{\"a\":1}\n{\"a\":2}\n")
            ->assertOk();
    }

    #[Test]
    public function it_passes_a_malformed_json_body_outside_the_paths_to_the_route(): void
    {
        $this->call('POST', 'errors/outside/validate', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], content: '{"title":')
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['title']]);
    }
}
