<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController\Concerns;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Support\Http\Api\Console\Enums\Endpoint;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\References\Controller;
use Support\Http\Api\References\Precondition;
use Support\Http\Api\References\Route;
use Tests\TestCase;
use Tooling\Composer\Composer;

/**
 * @mixin TestCase
 */
trait GeneratesRestTestCases
{
    private Controller $showController {
        get => Controller::make(
            Route::make('V1', $this->entity, EndpointType::Rest, Endpoint::Show->value),
        );
    }

    #[Test]
    public function it_creates_all_endpoint_files(): void
    {
        Composer::fake();

        $input = [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => EndpointType::Rest->value,
        ];

        $this->artisan($this->command, $input)->expectsChoice(
            'What endpoint would you like to create?',
            Endpoint::Show->value,
            array_column(Endpoint::cases(), 'value')
        )->assertSuccessful();

        $this->assertTrue(File::exists($this->showController->filePath->toString()));
        $this->assertTrue(File::exists($this->showController->authorizer->filePath->toString()));
        $this->assertTrue(File::exists($this->showController->validator->filePath->toString()));
        $this->assertTrue(File::exists($this->showController->test->filePath->toString()));
    }

    #[Test]
    public function it_creates_only_authorizer_when_validator_is_opted_out(): void
    {
        Composer::fake();

        $input = [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => EndpointType::Rest->value,
            '--no-validator' => true,
        ];

        $this->artisan($this->command, $input)->expectsChoice(
            'What endpoint would you like to create?',
            Endpoint::Show->value,
            array_column(Endpoint::cases(), 'value')
        )->assertSuccessful();

        $this->assertTrue(File::exists($this->showController->authorizer->filePath->toString()));
        $this->assertFalse(File::exists($this->showController->validator->filePath->toString()));
    }

    #[Test]
    public function it_creates_only_validator_when_authorizer_is_opted_out(): void
    {
        Composer::fake();

        $input = [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => EndpointType::Rest->value,
            '--no-authorizer' => true,
        ];

        $this->artisan($this->command, $input)->expectsChoice(
            'What endpoint would you like to create?',
            Endpoint::Show->value,
            array_column(Endpoint::cases(), 'value')
        )->assertSuccessful();

        $this->assertTrue(File::exists($this->showController->validator->filePath->toString()));
        $this->assertFalse(File::exists($this->showController->authorizer->filePath->toString()));
    }

    #[Test]
    public function it_populates_route_attributes_in_controller(): void
    {
        Composer::fake();

        $input = [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => EndpointType::Rest->value,
        ];

        $this->artisan($this->command, $input)->expectsChoice(
            'What endpoint would you like to create?',
            Endpoint::Show->value,
            array_column(Endpoint::cases(), 'value')
        )->assertSuccessful();

        tap(File::get($this->showController->filePath->toString()), function (string $contents): void {
            $this->assertStringContainsString($this->showController->route->routeName->toString(), $contents);
            $this->assertStringContainsString($this->showController->route->uri->toString(), $contents);
            $this->assertStringContainsString('Method::'.$this->showController->route->method->name, $contents);
            $this->assertStringContainsString($this->showController->entity->fqcn->toString(), $contents);
            $this->assertStringContainsString(
                $this->showController->entity->name.' $'.$this->showController->route->controllerParameterName, $contents
            );
        });
    }

    #[Test]
    #[TestWith([Endpoint::Update])]
    #[TestWith([Endpoint::Delete])]
    public function it_creates_a_precondition_between_the_authorizer_and_the_validator_for_an_instance_write(Endpoint $endpoint): void
    {
        Composer::fake();

        $this->artisan($this->command, $this->restInput())->expectsChoice(
            'What endpoint would you like to create?',
            $endpoint->value,
            array_column(Endpoint::cases(), 'value')
        )->assertSuccessful();

        $controller = Controller::make(Route::make('V1', $this->entity, EndpointType::Rest, $endpoint->value, scope: $endpoint->scope()));
        $precondition = new Precondition(name: 'Precondition', baseNamespace: $controller->namespace);

        $this->assertStringContainsString(
            'Authorizer $authorizer, Precondition $precondition, Validator $validator, '.$controller->entity->name.' $'.$controller->route->controllerParameterName,
            File::get($controller->filePath->toString()),
        );
        $this->assertStringContainsString(
            "\$this->route('{$controller->route->routeParameterName}')",
            File::get($precondition->filePath->toString()),
        );
        $this->assertStringContainsString('{'.$controller->route->routeParameterName.'}', $controller->route->uri->toString());
    }

    #[Test]
    #[TestWith([Endpoint::Show])]
    #[TestWith([Endpoint::Store])]
    public function it_creates_no_precondition_for_a_read_or_a_collection_endpoint(Endpoint $endpoint): void
    {
        Composer::fake();

        $this->artisan($this->command, $this->restInput())->expectsChoice(
            'What endpoint would you like to create?',
            $endpoint->value,
            array_column(Endpoint::cases(), 'value')
        )->assertSuccessful();

        $controller = Controller::make(Route::make('V1', $this->entity, EndpointType::Rest, $endpoint->value, scope: $endpoint->scope()));

        $this->assertFalse(File::exists(new Precondition(name: 'Precondition', baseNamespace: $controller->namespace)->filePath->toString()));
        $this->assertStringNotContainsString('Precondition $precondition', File::get($controller->filePath->toString()));
    }

    #[Test]
    public function it_creates_no_precondition_when_the_precondition_is_opted_out(): void
    {
        Composer::fake();

        $this->artisan($this->command, [...$this->restInput(), '--no-precondition' => true])->expectsChoice(
            'What endpoint would you like to create?',
            Endpoint::Update->value,
            array_column(Endpoint::cases(), 'value')
        )->assertSuccessful();

        $controller = Controller::make(Route::make('V1', $this->entity, EndpointType::Rest, Endpoint::Update->value));

        $this->assertFalse(File::exists(new Precondition(name: 'Precondition', baseNamespace: $controller->namespace)->filePath->toString()));
        $this->assertStringContainsString(
            'Authorizer $authorizer, Validator $validator, '.$controller->entity->name.' $'.$controller->route->controllerParameterName,
            File::get($controller->filePath->toString()),
        );
    }

    #[Test]
    public function it_creates_a_precondition_when_the_precondition_is_opted_in(): void
    {
        Composer::fake();

        $this->artisan($this->command, [...$this->restInput(), '--precondition' => true])->expectsChoice(
            'What endpoint would you like to create?',
            Endpoint::Show->value,
            array_column(Endpoint::cases(), 'value')
        )->assertSuccessful();

        $this->assertTrue(File::exists(new Precondition(name: 'Precondition', baseNamespace: $this->showController->namespace)->filePath->toString()));
        $this->assertStringContainsString('Authorizer $authorizer, Precondition $precondition, Validator $validator', File::get($this->showController->filePath->toString()));
    }

    /**
     * @return array<string, mixed>
     */
    private function restInput(): array
    {
        return [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => EndpointType::Rest->value,
        ];
    }
}
