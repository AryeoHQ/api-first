<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\HttpFoundation\Response;
use Tests\Fixtures\Support\Articles\Article;
use Tests\Fixtures\Support\Http\Api\Errors\HiddenArticlePolicy;
use Tests\TestCase;

#[CoversClass(Renderer::class)]
final class RendererTest extends TestCase
{
    use WithWorkbench;

    private const string GENERIC_DETAIL = 'The API cannot complete the request.';

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('articles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestampsTz();
        });
    }

    #[Test]
    public function it_renders_a_request_without_a_token_as_401(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/authenticated'),
            Response::HTTP_UNAUTHORIZED,
            [['code' => 'authentication', 'detail' => 'The request carries no access token.']],
        );
    }

    #[Test]
    public function it_renders_a_rejected_token_as_401(): void
    {
        $this->assertErrors(
            $this->withToken('expired-token')->getJson('api/errors/authenticated'),
            Response::HTTP_UNAUTHORIZED,
            [['code' => 'authentication', 'detail' => 'The access token is invalid or expired.']],
        );
    }

    #[Test]
    public function it_renders_a_failed_authorizer_as_403(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/forbidden'),
            Response::HTTP_FORBIDDEN,
            [['code' => 'authorization', 'detail' => 'The caller cannot perform this operation.']],
        );
    }

    #[Test]
    public function it_renders_a_missing_bound_model_as_404(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/articles/'.Str::uuid()),
            Response::HTTP_NOT_FOUND,
            [['code' => 'error', 'detail' => 'No resource exists at this path.']],
        );
    }

    #[Test]
    public function it_renders_a_method_not_allowed_on_a_matched_path_as_405(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/method-not-allowed'),
            Response::HTTP_METHOD_NOT_ALLOWED,
            [['code' => 'error', 'detail' => 'The GET method is not allowed on this path.']],
        )->assertHeader('Allow', 'GET');
    }

    #[Test]
    public function it_renders_a_method_the_router_rejects_as_405(): void
    {
        $this->assertErrors(
            $this->deleteJson('api/errors/validate'),
            Response::HTTP_METHOD_NOT_ALLOWED,
            [['code' => 'error', 'detail' => 'The DELETE method is not allowed on this path.']],
        )->assertHeader('Allow', 'GET, HEAD, POST');
    }

    #[Test]
    public function it_renders_an_unknown_path_as_404(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/nowhere'),
            Response::HTTP_NOT_FOUND,
            [['code' => 'error', 'detail' => 'No resource exists at this path.']],
        );
    }

    #[Test]
    public function it_renders_maintenance_mode_as_503_with_retry_after(): void
    {
        config()->set('app.maintenance.driver', 'array');

        $this->app->maintenanceMode()->activate(['retry' => 60]);

        $this->assertErrors(
            $this->getJson('api/errors/forbidden'),
            Response::HTTP_SERVICE_UNAVAILABLE,
            [['code' => 'error', 'detail' => 'The service cannot serve requests at the moment.']],
        )->assertHeader('Retry-After', '60');
    }

    #[Test]
    public function it_renders_one_error_per_failed_rule_as_422(): void
    {
        $this->assertErrors(
            $this->postJson('api/errors/validate?filters[status]=archived', ['photo_count' => -1]),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            [
                ['code' => 'validation', 'detail' => 'The title field is required.', 'source' => ['pointer' => '/title']],
                ['code' => 'validation', 'detail' => 'The photo count field must be at least 0.', 'source' => ['pointer' => '/photo_count']],
                ['code' => 'validation', 'detail' => 'The selected filters.status is invalid.', 'source' => ['parameter' => 'filters[status]']],
            ],
        );
    }

    #[Test]
    public function it_renders_a_stale_precondition_as_412_without_an_etag(): void
    {
        $this->assertErrors(
            $this->postJson('api/errors/precondition-failed'),
            Response::HTTP_PRECONDITION_FAILED,
            [['code' => 'error', 'detail' => 'The If-Match precondition does not match the current resource.', 'source' => ['header' => 'If-Match']]],
        )->assertHeaderMissing('ETag');
    }

    #[Test]
    public function it_drops_a_caller_supplied_etag_from_a_412_and_keeps_other_headers(): void
    {
        $this->assertErrors(
            $this->postJson('api/errors/precondition-failed-with-headers'),
            Response::HTTP_PRECONDITION_FAILED,
            [['code' => 'error', 'detail' => 'The If-Match precondition does not match the current resource.', 'source' => ['header' => 'If-Match']]],
        )->assertHeaderMissing('ETag')->assertHeader('X-Request-Trace', 'trace-1');
    }

    #[Test]
    public function it_renders_symfonys_precondition_failed_exception_as_412_without_an_etag(): void
    {
        $this->assertErrors(
            $this->postJson('api/errors/symfony/precondition-failed'),
            Response::HTTP_PRECONDITION_FAILED,
            [['code' => 'error', 'detail' => 'The If-Match precondition does not match the current resource.', 'source' => ['header' => 'If-Match']]],
        )->assertHeaderMissing('ETag');
    }

    #[Test]
    public function it_renders_symfonys_precondition_required_exception_as_428(): void
    {
        $this->assertErrors(
            $this->postJson('api/errors/symfony/precondition-required'),
            Response::HTTP_PRECONDITION_REQUIRED,
            [['code' => 'error', 'detail' => 'This operation requires the If-Match header.', 'source' => ['header' => 'If-Match']]],
        );
    }

    #[Test]
    public function it_renders_a_missing_precondition_as_428(): void
    {
        $this->assertErrors(
            $this->postJson('api/errors/precondition-required'),
            Response::HTTP_PRECONDITION_REQUIRED,
            [['code' => 'error', 'detail' => 'This operation requires the If-Match header.', 'source' => ['header' => 'If-Match']]],
        );
    }

    #[Test]
    public function it_renders_an_aborted_412_as_a_stale_precondition(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/abort/412'),
            Response::HTTP_PRECONDITION_FAILED,
            [['code' => 'error', 'detail' => 'The If-Match precondition does not match the current resource.', 'source' => ['header' => 'If-Match']]],
        );
    }

    #[Test]
    public function it_renders_an_aborted_428_as_a_missing_precondition(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/abort/428'),
            Response::HTTP_PRECONDITION_REQUIRED,
            [['code' => 'error', 'detail' => 'This operation requires the If-Match header.', 'source' => ['header' => 'If-Match']]],
        );
    }

    #[Test]
    public function it_drops_the_etag_from_an_aborted_412_and_keeps_other_headers(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/abort/412/headers'),
            Response::HTTP_PRECONDITION_FAILED,
            [['code' => 'error', 'detail' => 'The If-Match precondition does not match the current resource.', 'source' => ['header' => 'If-Match']]],
        )->assertHeaderMissing('ETag')->assertHeader('X-Request-Trace', 'trace-1');
    }

    #[Test]
    public function it_keeps_the_headers_of_an_aborted_428(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/abort/428/headers'),
            Response::HTTP_PRECONDITION_REQUIRED,
            [['code' => 'error', 'detail' => 'This operation requires the If-Match header.', 'source' => ['header' => 'If-Match']]],
        )->assertHeader('ETag', '"33a64df5"')->assertHeader('X-Request-Trace', 'trace-1');
    }

    #[Test]
    public function it_renders_a_throttled_request_as_429_with_retry_after_and_a_reset_in_seconds(): void
    {
        $this->getJson('api/errors/throttled')->assertOk();

        $response = $this->assertErrors(
            $this->getJson('api/errors/throttled'),
            Response::HTTP_TOO_MANY_REQUESTS,
            [['code' => 'error', 'detail' => 'The request exceeds a rate limit.']],
        )->assertHeader('Retry-After');

        $response->assertHeader('X-RateLimit-Reset', $response->baseResponse->headers->get('Retry-After'));
    }

    #[Test]
    #[TestWith(['2026-10-21 07:27:00', '60'])]
    #[TestWith(['2026-10-21 07:29:00', '0'])]
    public function it_renders_a_retry_after_date_as_a_reset_in_seconds(string $now, string $reset): void
    {
        $this->travelTo(Date::parse($now, 'UTC'));

        $this->assertErrors(
            $this->getJson('api/errors/throttled-until'),
            Response::HTTP_TOO_MANY_REQUESTS,
            [['code' => 'error', 'detail' => 'The request exceeds a rate limit.']],
        )->assertHeader('Retry-After', 'Wed, 21 Oct 2026 07:28:00 GMT')->assertHeader('X-RateLimit-Reset', $reset);
    }

    #[Test]
    public function it_renders_an_unexpected_failure_as_500_without_its_details(): void
    {
        config()->set('app.debug', false);

        $response = $this->assertErrors(
            $this->getJson('api/errors/fail'),
            Response::HTTP_INTERNAL_SERVER_ERROR,
            [['code' => 'error', 'detail' => 'The server failed to complete the request.']],
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
            [['code' => 'error', 'detail' => 'The server failed to complete the request.']],
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
            [['code' => 'error', 'detail' => 'The If-Match precondition does not match the current resource.', 'source' => ['header' => 'If-Match']]],
        )->assertHeaderMissing('ETag');
    }

    #[Test]
    public function it_renders_symfonys_precondition_failed_exception_on_a_legacy_controller_as_412(): void
    {
        $this->assertErrors(
            $this->postJson('api/errors/legacy/symfony/precondition-failed'),
            Response::HTTP_PRECONDITION_FAILED,
            [['code' => 'error', 'detail' => 'The If-Match precondition does not match the current resource.', 'source' => ['header' => 'If-Match']]],
        )->assertHeaderMissing('ETag');
    }

    #[Test]
    public function it_renders_an_aborted_412_on_a_legacy_controller_in_the_envelope(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/legacy/abort/412'),
            Response::HTTP_PRECONDITION_FAILED,
            [['code' => 'error', 'detail' => 'The If-Match precondition does not match the current resource.', 'source' => ['header' => 'If-Match']]],
        );
    }

    #[Test]
    public function it_renders_an_aborted_428_on_a_legacy_controller_in_the_envelope(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/legacy/abort/428'),
            Response::HTTP_PRECONDITION_REQUIRED,
            [['code' => 'error', 'detail' => 'This operation requires the If-Match header.', 'source' => ['header' => 'If-Match']]],
        );
    }

    #[Test]
    public function it_leaves_a_path_outside_the_patterns_to_the_framework(): void
    {
        $this->postJson('errors/outside/validate')
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['title']])
            ->assertJsonMissingPath('meta');
    }

    #[Test]
    public function it_reads_the_paths_from_config(): void
    {
        config()->set('api-first.errors.paths', ['v2/*']);

        $this->postJson('api/errors/validate')
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['title']])
            ->assertJsonMissingPath('meta');
    }

    #[Test]
    public function it_matches_a_pattern_whose_first_segment_contains_a_dot_as_a_path(): void
    {
        config()->set('api-first.errors.paths', ['v1.0/*']);

        $this->assertErrors(
            $this->getJson('v1.0/nowhere'),
            Response::HTTP_NOT_FOUND,
            [['code' => 'error', 'detail' => 'No resource exists at this path.']],
        );
    }

    #[Test]
    public function it_renders_the_api_prefix_root_as_404(): void
    {
        $this->assertErrors(
            $this->getJson('api'),
            Response::HTTP_NOT_FOUND,
            [['code' => 'error', 'detail' => 'No resource exists at this path.']],
        );
    }

    #[Test]
    public function it_treats_an_empty_bearer_token_as_no_token(): void
    {
        $this->assertErrors(
            $this->withHeader('Authorization', 'Bearer ')->getJson('api/errors/authenticated'),
            Response::HTTP_UNAUTHORIZED,
            [['code' => 'authentication', 'detail' => 'The request carries no access token.']],
        );
    }

    #[Test]
    public function it_treats_basic_credentials_as_no_token(): void
    {
        $this->assertErrors(
            $this->withHeader('Authorization', 'Basic dXNlcjpzZWNyZXQ=')->getJson('api/errors/authenticated'),
            Response::HTTP_UNAUTHORIZED,
            [['code' => 'authentication', 'detail' => 'The request carries no access token.']],
        );
    }

    #[Test]
    public function it_renders_a_policy_that_denies_as_not_found_as_403(): void
    {
        Gate::policy(Article::class, HiddenArticlePolicy::class);

        $article = Article::factory()->create();

        $this->assertErrors(
            $this->getJson("api/errors/articles/{$article->getKey()}/hidden"),
            Response::HTTP_FORBIDDEN,
            [['code' => 'authorization', 'detail' => 'The caller cannot perform this operation.']],
        );
    }

    #[Test]
    public function it_renders_a_missing_passport_scope_as_403_naming_the_scope(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/missing-scope'),
            Response::HTTP_FORBIDDEN,
            [['code' => 'authorization', 'detail' => 'The access token does not grant the articles:write scope.']],
        );
    }

    #[Test]
    public function it_renders_a_missing_sanctum_ability_as_403_naming_the_ability(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/missing-ability'),
            Response::HTTP_FORBIDDEN,
            [['code' => 'authorization', 'detail' => 'The access token does not grant the articles:write scope.']],
        );
    }

    #[Test]
    public function it_renders_a_bad_request_as_400(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/bad-request'),
            Response::HTTP_BAD_REQUEST,
            [['code' => 'error', 'detail' => 'The API cannot parse the request.']],
        );
    }

    #[Test]
    public function it_renders_a_malformed_json_body_as_400(): void
    {
        $this->assertErrors(
            $this->call('POST', 'api/errors/validate', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], content: '{"title":'),
            Response::HTTP_BAD_REQUEST,
            [['code' => 'error', 'detail' => 'The API cannot parse the request.']],
        );
    }

    #[Test]
    public function it_renders_an_unsupported_media_type_as_415(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/unsupported-media-type'),
            Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
            [['code' => 'error', 'detail' => 'The request body must use the application/json media type.']],
        );
    }

    #[Test]
    public function it_renders_an_unavailable_service_as_503_with_retry_after(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/unavailable'),
            Response::HTTP_SERVICE_UNAVAILABLE,
            [['code' => 'error', 'detail' => 'The service cannot serve requests at the moment.']],
        )->assertHeader('Retry-After', '30');
    }

    #[Test]
    #[TestWith([401, 'authentication'])]
    #[TestWith([409, 'error'])]
    #[TestWith([418, 'error'])]
    #[TestWith([422, 'validation'])]
    #[TestWith([502, 'error'])]
    public function it_renders_any_other_http_status_in_its_category_without_its_message(int $status, string $code): void
    {
        $response = $this->assertErrors(
            $this->getJson("api/errors/abort/{$status}"),
            $status,
            [['code' => $code, 'detail' => self::GENERIC_DETAIL]],
        );

        $this->assertStringNotContainsString('Tenant A', (string) $response->baseResponse->getContent());
    }

    #[Test]
    public function it_logs_no_warning_for_an_unmapped_status(): void
    {
        $log = Log::spy();

        $this->getJson('api/errors/abort/409')->assertConflict();

        $log->shouldNotHaveReceived('warning');
    }

    #[Test]
    public function it_renders_a_validation_failure_with_another_status_in_the_category_of_that_status(): void
    {
        $this->assertErrors(
            $this->getJson('api/errors/validation-status'),
            Response::HTTP_BAD_REQUEST,
            [['code' => 'error', 'detail' => self::GENERIC_DETAIL]],
        );
    }

    #[Test]
    public function it_reads_a_single_path_pattern_from_config(): void
    {
        config()->set('api-first.errors.paths', 'errors/outside/*');

        $this->postJson('errors/outside/validate', ['title' => 'Dusk shoot', 'photo_count' => -1])
            ->assertUnprocessable()
            ->assertJsonPath('errors.*.code', ['validation']);
    }

    #[Test]
    public function it_leaves_errors_to_the_framework_without_configured_paths(): void
    {
        config()->set('api-first.errors.paths', []);

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

        return $response->assertHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
