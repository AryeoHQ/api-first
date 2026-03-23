<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Commands\MakeController\Concerns\GeneratesActionTestCases;
use Support\Http\Api\Console\Commands\MakeController\Concerns\GeneratesRestTestCases;
use Support\Http\Api\Console\Enums\Endpoint;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\Console\Enums\Scope;
use Support\Http\Api\References\Controller;
use Support\Http\Api\References\Route;
use Tests\TestCase;
use Tooling\GeneratorCommands\References\Contracts\Reference;
use Tooling\GeneratorCommands\Testing\Concerns\CleansUpGeneratorCommands;
use Tooling\GeneratorCommands\Testing\Concerns\GeneratesFileTestCases;

#[CoversClass(MakeController::class)]
class MakeControllerTest extends TestCase
{
    use CleansUpGeneratorCommands;
    use GeneratesActionTestCases;
    use GeneratesFileTestCases;
    use GeneratesRestTestCases;

    private Entity $entity {
        get => new Entity(name: class_basename(static::class), baseNamespace: 'Workbench\\App\\');
    }

    private Controller $controller {
        get => Controller::make(
            Route::make('V1', $this->entity, EndpointType::Rest, 'index', scope: Scope::Resource),
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
            '--type' => 'REST',
        ];
    }

    /** @var array<array-key, string> */
    protected array $files {
        get => [
            $this->controller->directory->append('/*')->toString(),
            $this->showController->directory->append('/*')->toString(),
            $this->actionController->directory->append('/*')->toString(),
            $this->resourceActionController->directory->append('/*')->toString(),
        ];
    }

    #[Test]
    public function it_generates_a_file_with_the_correct_namespace(): void
    {
        $this->artisan($this->command, $this->baselineInput)->expectsChoice(
            'What endpoint would you like to create?',
            'index',
            array_column(Endpoint::cases(), 'value')
        )->assertSuccessful();

        $contents = file_get_contents($this->expectedFilePath);

        $this->assertStringContainsString(
            'namespace '.$this->reference->namespace->after('\\').';',
            $contents,
        );
    }

    #[Test]
    public function it_prompts_for_endpoint_type_when_option_is_invalid(): void
    {
        $input = [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => 'invalid',
        ];

        $this->artisan($this->command, $input)->expectsChoice(
            'What type of endpoint would you like to create?', 'REST', array_column(EndpointType::cases(), 'value')
        )->expectsChoice(
            'What endpoint would you like to create?', 'show', array_column(Endpoint::cases(), 'value')
        )->assertSuccessful();

        $this->assertFileExists($this->showController->filePath->toString());
    }
}
