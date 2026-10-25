<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Commands\MakeController\Concerns\GeneratesActionTestCases;
use Support\Http\Api\Console\Commands\MakeController\Concerns\GeneratesRestTestCases;
use Support\Http\Api\Console\Enums\Endpoint;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\Console\Enums\Scope;
use Support\Http\Api\References\Controller;
use Support\Http\Api\References\Precondition;
use Support\Http\Api\References\Route;
use Tests\Fixtures\Support\Schemas\ApiVersion;
use Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions\Entities\Widgets\Widget;
use Tests\TestCase;
use Tooling\Composer\Composer;
use Tooling\GeneratorCommands\References\Contracts\Reference;
use Tooling\GeneratorCommands\Testing\Concerns\GeneratesFileTestCases;

#[CoversClass(MakeController::class)]
#[WithConfig('api-resource-schema.version', ApiVersion::class)]
class MakeControllerTest extends TestCase
{
    use GeneratesActionTestCases;
    use GeneratesFileTestCases;
    use GeneratesRestTestCases;

    private Entity $entity {
        get => new Entity(name: class_basename(static::class), baseNamespace: 'App\\');
    }

    private Entity $revisionedEntity {
        get => Entity::fromFqcn(Widget::class);
    }

    private Controller $controller {
        get => Controller::make(
            Route::make('V1', $this->entity, EndpointType::Rest, Endpoint::Index->value, scope: Scope::Resource),
        );
    }

    public Reference $reference {
        get => $this->controller;
    }

    /** @var array<string, mixed> */
    public array $baselineInput {
        get => [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => EndpointType::Rest->value,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('widgets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->concurrencyRevision();
            $table->timestampsTz();
        });
    }

    #[Test]
    public function it_generates_a_file_with_the_correct_namespace(): void
    {
        Composer::fake();

        $this->artisan($this->command, $this->baselineInput)->expectsChoice(
            'What endpoint would you like to create?',
            Endpoint::Index->value,
            array_column(Endpoint::cases(), 'value')
        )->assertSuccessful();

        $contents = File::get($this->expectedFilePath);

        $this->assertStringContainsString(
            'namespace '.$this->reference->namespace->after('\\').';',
            $contents,
        );
    }

    #[Test]
    public function it_prompts_for_endpoint_type_when_option_is_invalid(): void
    {
        Composer::fake();

        $input = [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => 'invalid',
        ];

        $this->artisan($this->command, $input)->expectsChoice(
            'What type of endpoint would you like to create?', EndpointType::Rest->value, array_column(EndpointType::cases(), 'value')
        )->expectsChoice(
            'What endpoint would you like to create?', Endpoint::Show->value, array_column(Endpoint::cases(), 'value')
        )->assertSuccessful();

        $this->assertTrue(File::exists($this->showController->filePath->toString()));
    }

    private function assertGeneratedEndpointRejectsAStaleIfMatch(Controller $controller): void
    {
        $precondition = new Precondition(name: 'Precondition', baseNamespace: $controller->namespace);

        foreach ([$controller->authorizer, $precondition, $controller->validator, $controller] as $reference) {
            $this->requireGenerated($reference->filePath->toString());
        }

        Router::middleware(SubstituteBindings::class)->match(
            [$controller->route->method->value],
            $controller->route->uri->toString(),
            $controller->fqcn->ltrim('\\')->toString(),
        );

        $widget = Widget::query()->create();
        $uri = str_replace('{'.$controller->route->routeParameterName.'}', (string) $widget->getKey(), $controller->route->uri->toString());

        $this->json($controller->route->method->value, $uri, [], ['If-Match' => '"v1.999.0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f"'])
            ->assertStatus(412);
        $this->json($controller->route->method->value, $uri, [], ['If-Match' => '"v1.1.0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f0f"'])
            ->assertSuccessful();
    }

    private function requireGenerated(string $path): void
    {
        $file = (string) tempnam(sys_get_temp_dir(), 'generated');

        file_put_contents($file, File::get($path));

        require $file;

        unlink($file);
    }
}
