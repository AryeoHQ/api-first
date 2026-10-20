<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ClosureValidationRule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Http\Api\Errors\BookingWindow;
use Tests\TestCase;

#[CoversClass(ValidationErrors::class)]
final class ValidationErrorsTest extends TestCase
{
    use WithWorkbench;

    private const string PLACEHOLDER = 'The request failed for a reason this API does not catalogue yet.';

    #[Test]
    public function it_suffixes_a_size_rule_with_the_attribute_type(): void
    {
        $this->post('api/errors/rules', [
            'name' => 'ab',
            'tags' => ['one'],
            'photo' => UploadedFile::fake()->create('photo.jpg', 2),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.*.code', ['validation.min_string', 'validation.min_array', 'validation.max_file'])
            ->assertJsonPath('errors.*.source', [['pointer' => '/name'], ['pointer' => '/tags'], ['pointer' => '/photo']]);
    }

    #[Test]
    public function it_names_a_rule_object_by_its_class(): void
    {
        $this->getJson('api/errors/rules?page[size]=500')
            ->assertUnprocessable()
            ->assertExactJson([
                'errors' => [
                    ['code' => 'validation.page_size', 'detail' => 'The page.size must be between 1 and 100.', 'source' => ['parameter' => 'page[size]']],
                ],
                'meta' => [],
            ]);
    }

    #[Test]
    public function it_points_into_nested_and_escaped_body_keys(): void
    {
        $this->postJson('api/errors/rules', ['items' => [['name' => 'first'], []], 'a/b' => 'x'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.*.code', ['validation.integer', 'validation.required'])
            ->assertJsonPath('errors.*.source', [['pointer' => '/a~1b'], ['pointer' => '/items/1/name']]);
    }

    #[Test]
    public function it_names_a_missing_key_on_a_safe_method_as_a_parameter(): void
    {
        $this->getJson('api/errors/validate')
            ->assertUnprocessable()
            ->assertJsonPath('errors.*.source', [['parameter' => 'title']]);
    }

    #[Test]
    public function it_names_a_query_key_on_an_unsafe_method_as_a_parameter(): void
    {
        $this->postJson('api/errors/validate?filters[status]=archived', ['title' => 'Dusk shoot'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.*.source', [['parameter' => 'filters[status]']]);
    }

    #[Test]
    public function it_renders_an_after_hook_message_as_a_placeholder(): void
    {
        $log = Log::spy();

        $this->postJson('api/errors/after', ['title' => 'Dusk shoot'])
            ->assertUnprocessable()
            ->assertExactJson([
                'errors' => [
                    ['code' => 'validation.unspecified', 'detail' => self::PLACEHOLDER, 'source' => ['pointer' => '/window']],
                ],
                'meta' => [],
            ]);

        $log->shouldHaveReceived('warning')->with('api-first.errors.uncatalogued', $this->logged(null, null, 'api/errors/after'))->once();
    }

    #[Test]
    public function it_renders_a_message_from_with_messages_as_a_placeholder(): void
    {
        $this->postJson('api/errors/with-messages')
            ->assertUnprocessable()
            ->assertExactJson([
                'errors' => [
                    ['code' => 'validation.unspecified', 'detail' => self::PLACEHOLDER, 'source' => ['pointer' => '/window']],
                ],
                'meta' => [],
            ]);
    }

    #[Test]
    public function it_renders_an_uncatalogued_rule_as_a_placeholder_and_logs_its_derived_code(): void
    {
        $log = Log::spy();

        $this->postJson('api/errors/uncatalogued', ['password' => 'abc', 'nickname' => 'dusk', 'window' => 'evening'])
            ->assertUnprocessable()
            ->assertExactJson([
                'errors' => [
                    ['code' => 'validation.unspecified', 'detail' => self::PLACEHOLDER, 'source' => ['pointer' => '/password']],
                    ['code' => 'validation.unspecified', 'detail' => self::PLACEHOLDER, 'source' => ['pointer' => '/nickname']],
                    ['code' => 'validation.unspecified', 'detail' => self::PLACEHOLDER, 'source' => ['pointer' => '/window']],
                ],
                'meta' => [],
            ]);

        $log->shouldHaveReceived('warning')->with('api-first.errors.uncatalogued', $this->logged('validation.password', Password::class, 'api/errors/uncatalogued'))->once();
        $log->shouldHaveReceived('warning')->with('api-first.errors.uncatalogued', $this->logged('validation.closure_validation_rule', ClosureValidationRule::class, 'api/errors/uncatalogued'))->once();
        $log->shouldHaveReceived('warning')->with('api-first.errors.uncatalogued', $this->logged('validation.booking_window', BookingWindow::class, 'api/errors/uncatalogued'))->once();
    }

    #[Test]
    public function it_renders_a_code_the_application_catalogues(): void
    {
        config()->set('api-first.errors.codes', ['validation.booking_window']);

        $this->postJson('api/errors/uncatalogued', ['window' => 'evening'])
            ->assertUnprocessable()
            ->assertExactJson([
                'errors' => [
                    ['code' => 'validation.booking_window', 'detail' => 'The booking window is closed.', 'source' => ['pointer' => '/window']],
                ],
                'meta' => [],
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function logged(null|string $code, null|string $rule, string $route): array
    {
        return [
            'status' => 422,
            'placeholder' => 'validation.unspecified',
            'derived_code' => $code,
            'exception' => ValidationException::class,
            'rule' => $rule,
            'route' => $route,
            'method' => 'POST',
        ];
    }
}
