<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use Illuminate\Http\UploadedFile;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(ValidationErrors::class)]
final class ValidationErrorsTest extends TestCase
{
    use WithWorkbench;

    #[Test]
    public function it_points_at_the_form_and_file_fields_of_a_multipart_body(): void
    {
        $this->post('api/errors/rules', [
            'name' => 'ab',
            'tags' => ['one'],
            'photo' => UploadedFile::fake()->create('photo.jpg', 2),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.*.code', ['validation', 'validation', 'validation'])
            ->assertJsonPath('errors.*.source', [['pointer' => '/name'], ['pointer' => '/tags'], ['pointer' => '/photo']]);
    }

    #[Test]
    public function it_names_a_nested_query_key_as_a_bracketed_parameter(): void
    {
        $this->getJson('api/errors/rules?page[size]=500')
            ->assertUnprocessable()
            ->assertExactJson([
                'errors' => [
                    ['code' => 'validation', 'detail' => 'The page.size must be between 1 and 100.', 'source' => ['parameter' => 'page[size]']],
                ],
                'meta' => [],
            ]);
    }

    #[Test]
    public function it_points_into_nested_and_escaped_body_keys(): void
    {
        $this->postJson('api/errors/rules', ['items' => [['name' => 'first'], []], 'a/b' => 'x'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.*.code', ['validation', 'validation'])
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
    public function it_renders_an_after_hook_message_with_its_detail(): void
    {
        $this->postJson('api/errors/after', ['title' => 'Dusk shoot'])
            ->assertUnprocessable()
            ->assertExactJson([
                'errors' => [
                    ['code' => 'validation', 'detail' => 'The booking window is closed.', 'source' => ['pointer' => '/window']],
                ],
                'meta' => [],
            ]);
    }

    #[Test]
    public function it_renders_a_message_from_with_messages_with_its_detail(): void
    {
        $this->postJson('api/errors/with-messages')
            ->assertUnprocessable()
            ->assertExactJson([
                'errors' => [
                    ['code' => 'validation', 'detail' => 'The booking window is closed.', 'source' => ['pointer' => '/window']],
                ],
                'meta' => [],
            ]);
    }

    #[Test]
    public function it_renders_a_password_closure_and_rule_object_message_with_its_detail(): void
    {
        $this->postJson('api/errors/custom-rules', ['password' => 'abc', 'nickname' => 'dusk', 'window' => 'evening'])
            ->assertUnprocessable()
            ->assertExactJson([
                'errors' => [
                    ['code' => 'validation', 'detail' => 'The password field must be at least 8 characters.', 'source' => ['pointer' => '/password']],
                    ['code' => 'validation', 'detail' => 'The nickname is taken.', 'source' => ['pointer' => '/nickname']],
                    ['code' => 'validation', 'detail' => 'The booking window is closed.', 'source' => ['pointer' => '/window']],
                ],
                'meta' => [],
            ]);
    }
}
