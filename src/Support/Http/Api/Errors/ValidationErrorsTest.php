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
    public function it_leaves_a_message_without_a_failed_rule_to_the_framework(): void
    {
        $this->postJson('api/errors/after', ['title' => 'Dusk shoot'])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['window']])
            ->assertJsonMissingPath('meta');
    }
}
