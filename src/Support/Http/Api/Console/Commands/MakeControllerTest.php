<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\References\Controller;
use Tests\TestCase;
use Tooling\GeneratorCommands\References\Contracts\Reference;
use Tooling\GeneratorCommands\Testing\Concerns\CleansUpGeneratorCommands;
use Tooling\GeneratorCommands\Testing\Concerns\GeneratesFileTestCases;

#[CoversClass(MakeController::class)]
class MakeControllerTest extends TestCase
{
    use CleansUpGeneratorCommands;
    use GeneratesFileTestCases;

    private Entity $entity {
        get => new Entity(name: class_basename(static::class), baseNamespace: 'Workbench\\App\\');
    }

    private Controller $controller {
        get => Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Rest,
            endpointName: 'index',
        );
    }

    public Reference $reference {
        get => $this->controller;
    }

    /** @var array<array-key, string> */
    protected array $files {
        get => [
            $this->controller->directory->append('/*')->toString(),
        ];
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

    #[Test]
    public function it_creates_all_endpoint_files(): void
    {
        $this->artisan($this->command, $this->baselineInput)->assertSuccessful();

        $this->assertFileExists($this->controller->filePath->toString());
        $this->assertFileExists($this->controller->authorizer->filePath->toString());
        $this->assertFileExists($this->controller->validator->filePath->toString());
        $this->assertFileExists($this->controller->test->filePath->toString());
    }

    #[Test]
    public function it_creates_only_authorizer_when_validator_is_opted_out(): void
    {
        $input = [...$this->baselineInput, '--no-validator' => true];

        $this->artisan($this->command, $input)->assertSuccessful();

        $this->assertFileExists($this->controller->authorizer->filePath->toString());
        $this->assertFileDoesNotExist($this->controller->validator->filePath->toString());
    }

    #[Test]
    public function it_creates_only_validator_when_authorizer_is_opted_out(): void
    {
        $input = [...$this->baselineInput, '--no-authorizer' => true];

        $this->artisan($this->command, $input)->assertSuccessful();

        $this->assertFileExists($this->controller->validator->filePath->toString());
        $this->assertFileDoesNotExist($this->controller->authorizer->filePath->toString());
    }

    #[Test]
    public function it_populates_route_attributes_in_controller(): void
    {
        $this->artisan($this->command, $this->baselineInput)->assertSuccessful();

        $contents = file_get_contents($this->controller->filePath->toString());

        $this->assertStringContainsString($this->controller->routeName->toString(), $contents);
        $this->assertStringContainsString($this->controller->uri->toString(), $contents);
        $this->assertStringContainsString($this->controller->httpMethod->toString(), $contents);
    }

    #[Test]
    public function it_creates_a_single_resource_endpoint(): void
    {
        $input = [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => 'REST',
            '--endpoint' => 'show',
        ];

        $this->artisan($this->command, $input)->assertSuccessful();

        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Rest,
            endpointName: 'show',
        );

        $contents = file_get_contents($controller->filePath->toString());

        $this->assertStringContainsString($controller->entity->fqcn->toString(), $contents);
        $this->assertStringContainsString($controller->modelBinding->toString(), $contents);
        $this->assertStringContainsString('Authorizer $authorizer', $contents);
        $this->assertStringContainsString('Validator $validator', $contents);
    }
}
