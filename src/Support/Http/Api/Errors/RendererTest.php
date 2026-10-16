<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

#[CoversClass(Renderer::class)]
final class RendererTest extends TestCase
{
    use WithWorkbench;

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('articles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestampsTz();
        });
    }

    #[Test]
    public function it_renders_a_request_without_a_token_as_401_missing_token(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/authenticated'),
            Response::HTTP_UNAUTHORIZED,
            [['code' => 'auth.missing_token', 'detail' => 'The request carries no access token.']],
        );
    }

    #[Test]
    public function it_renders_a_rejected_token_as_401_invalid_token(): void
    {
        $this->assertErrors(
            $this->withToken('expired-token')->getJson('api/errors/authenticated'),
            Response::HTTP_UNAUTHORIZED,
            [['code' => 'auth.invalid_token', 'detail' => 'The access token is invalid or expired.']],
        );
    }

    #[Test]
    public function it_renders_a_failed_authorizer_as_403(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/forbidden'),
            Response::HTTP_FORBIDDEN,
            [['code' => 'permission.denied', 'detail' => 'The caller cannot perform this operation.']],
        );
    }

    #[Test]
    public function it_renders_a_missing_bound_model_as_404(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/articles/'.Str::uuid()),
            Response::HTTP_NOT_FOUND,
            [['code' => 'not_found.resource', 'detail' => 'No resource exists at this path.']],
        );
    }

    #[Test]
    public function it_renders_a_method_not_allowed_on_a_matched_path_as_405(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/method-not-allowed'),
            Response::HTTP_METHOD_NOT_ALLOWED,
            [['code' => 'method.not_allowed', 'detail' => 'The GET method is not allowed on this path.']],
        )->assertHeader('Allow', 'GET');
    }

    #[Test]
    public function it_leaves_a_method_the_router_rejects_to_the_framework(): void
    {
        $this->deleteJson('api/errors/validate')
            ->assertMethodNotAllowed()
            ->assertJsonStructure(['message'])
            ->assertJsonMissingPath('errors');
    }

    #[Test]
    public function it_renders_one_error_per_failed_rule_as_422(): void
    {
        $this->assertErrors(
            $this->postJson('api/errors/validate?filters[status]=archived', ['photo_count' => -1]),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            [
                ['code' => 'validation.required', 'detail' => 'The title field is required.', 'source' => ['pointer' => '/title']],
                ['code' => 'validation.min_numeric', 'detail' => 'The photo count field must be at least 0.', 'source' => ['pointer' => '/photo_count']],
                ['code' => 'validation.in', 'detail' => 'The selected filters.status is invalid.', 'source' => ['parameter' => 'filters[status]']],
            ],
        );
    }

    #[Test]
    public function it_renders_a_stale_precondition_as_412_with_the_current_etag(): void
    {
        $this->assertErrors(
            $this->postJson('api/errors/precondition-failed'),
            Response::HTTP_PRECONDITION_FAILED,
            [['code' => 'precondition.failed', 'detail' => 'The If-Match precondition does not match the current resource.', 'source' => ['header' => 'If-Match']]],
        )->assertHeader('ETag', '"33a64df5"');
    }

    #[Test]
    public function it_renders_a_missing_precondition_as_428(): void
    {
        $this->assertErrors(
            $this->postJson('api/errors/precondition-required'),
            Response::HTTP_PRECONDITION_REQUIRED,
            [['code' => 'precondition.required', 'detail' => 'This operation requires the If-Match header.', 'source' => ['header' => 'If-Match']]],
        );
    }

    #[Test]
    public function it_renders_a_throttled_request_as_429_with_retry_after(): void
    {
        $this->getJson('api/errors/throttled')->assertOk();

        $this->assertErrors(
            $this->getJson('api/errors/throttled'),
            Response::HTTP_TOO_MANY_REQUESTS,
            [['code' => 'rate_limit.exceeded', 'detail' => 'The request exceeds a rate limit.']],
        )->assertHeader('Retry-After');
    }

    #[Test]
    public function it_renders_an_unexpected_failure_as_500_without_its_details(): void
    {
        config()->set('app.debug', false);

        $response = $this->assertErrors(
            $this->getJson('api/errors/fail'),
            Response::HTTP_INTERNAL_SERVER_ERROR,
            [['code' => 'server.internal_error', 'detail' => 'The server failed to complete the request.']],
        );

        $this->assertStringNotContainsString('db.internal', (string) $response->baseResponse->getContent());
    }

    #[Test]
    public function it_renders_an_unexpected_failure_as_500_without_its_details_in_debug_mode(): void
    {
        config()->set('app.debug', true);

        $response = $this->assertErrors(
            $this->getJson('api/errors/fail'),
            Response::HTTP_INTERNAL_SERVER_ERROR,
            [['code' => 'server.internal_error', 'detail' => 'The server failed to complete the request.']],
        );

        $this->assertStringNotContainsString('db.internal', (string) $response->baseResponse->getContent());
    }

    #[Test]
    public function it_leaves_a_legacy_controller_validation_failure_to_the_framework(): void
    {
        $this->postJson('api/errors/legacy/validate')
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['title']])
            ->assertJsonMissingPath('meta');
    }

    #[Test]
    public function it_renders_a_legacy_controller_stale_precondition_as_412(): void
    {
        $this->assertErrors(
            $this->postJson('api/errors/legacy/precondition-failed'),
            Response::HTTP_PRECONDITION_FAILED,
            [['code' => 'precondition.failed', 'detail' => 'The If-Match precondition does not match the current resource.', 'source' => ['header' => 'If-Match']]],
        )->assertHeader('ETag', '"33a64df5"');
    }

    #[Test]
    public function it_leaves_a_route_outside_the_middleware_groups_to_the_framework(): void
    {
        $this->postJson('errors/outside/validate')
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['title']])
            ->assertJsonMissingPath('meta');
    }

    #[Test]
    public function it_reads_the_middleware_groups_from_config(): void
    {
        config()->set('api-first.errors.middleware_groups', ['api-v2']);

        $this->postJson('api/errors/validate')
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['title']])
            ->assertJsonMissingPath('meta');
    }

    /**
     * @param  TestResponse<Response>  $response
     * @param  list<array<string, mixed>>  $errors
     * @return TestResponse<Response>
     */
    private function assertErrors(TestResponse $response, int $status, array $errors): TestResponse
    {
        $response->assertStatus($status)->assertExactJson(['errors' => $errors, 'meta' => []]);

        $this->assertStringEndsWith(',"meta":{}}', (string) $response->baseResponse->getContent());

        return $response;
    }
}
