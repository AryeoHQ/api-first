<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands;

use PHPUnit\Framework\Attributes\CoversClass;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Concerns\GeneratesActionTestCases;
use Support\Http\Api\Console\Concerns\GeneratesRestTestCases;
use Support\Http\Api\Console\Enums\EndpointType;
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
            Route::make('V1', $this->entity, EndpointType::Rest, 'index'),
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
            '--endpoint' => 'index',
        ];
    }

    /** @var array<array-key, string> */
    protected array $files {
        get => [
            $this->controller->directory->append('/*')->toString(),
            $this->showController->directory->append('/*')->toString(),
            $this->actionController->directory->append('/*')->toString(),
        ];
    }
}
