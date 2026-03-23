<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController\Concerns;

use PHPUnit\Framework\Attributes\Test;
use Support\Http\Api\Console\Enums\Endpoint;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\References\Controller;
use Support\Http\Api\References\Route;
use Tests\TestCase;

/**
 * @mixin TestCase
 */
trait GeneratesRestTestCases
{
    private Controller $showController {
        get => Controller::make(
            Route::make('V1', $this->entity, EndpointType::Rest, 'show'),
        );
    }

    #[Test]
    public function it_creates_all_endpoint_files(): void
    {
        $input = [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => 'REST',
        ];

        $this->artisan($this->command, $input)
            ->expectsChoice('What endpoint would you like to create?', 'show', array_column(Endpoint::cases(), 'value'))
            ->assertSuccessful();

        $this->assertFileExists($this->showController->filePath->toString());
        $this->assertFileExists($this->showController->authorizer->filePath->toString());
        $this->assertFileExists($this->showController->validator->filePath->toString());
        $this->assertFileExists($this->showController->test->filePath->toString());
    }

    #[Test]
    public function it_creates_only_authorizer_when_validator_is_opted_out(): void
    {
        $input = [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => 'REST',
            '--no-validator' => true,
        ];

        $this->artisan($this->command, $input)
            ->expectsChoice('What endpoint would you like to create?', 'show', array_column(Endpoint::cases(), 'value'))
            ->assertSuccessful();

        $this->assertFileExists($this->showController->authorizer->filePath->toString());
        $this->assertFileDoesNotExist($this->showController->validator->filePath->toString());
    }

    #[Test]
    public function it_creates_only_validator_when_authorizer_is_opted_out(): void
    {
        $input = [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => 'REST',
            '--no-authorizer' => true,
        ];

        $this->artisan($this->command, $input)
            ->expectsChoice('What endpoint would you like to create?', 'show', array_column(Endpoint::cases(), 'value'))
            ->assertSuccessful();

        $this->assertFileExists($this->showController->validator->filePath->toString());
        $this->assertFileDoesNotExist($this->showController->authorizer->filePath->toString());
    }

    #[Test]
    public function it_populates_route_attributes_in_controller(): void
    {
        $input = [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => 'REST',
        ];

        $this->artisan($this->command, $input)
            ->expectsChoice('What endpoint would you like to create?', 'show', array_column(Endpoint::cases(), 'value'))
            ->assertSuccessful();

        tap(file_get_contents($this->showController->filePath->toString()), function (string $contents): void {
            $this->assertStringContainsString($this->showController->route->routeName->toString(), $contents);
            $this->assertStringContainsString($this->showController->route->uri->toString(), $contents);
            $this->assertStringContainsString('Method::'.$this->showController->route->method->name, $contents);
            $this->assertStringContainsString($this->showController->entity->fqcn->toString(), $contents);
            $this->assertStringContainsString($this->showController->entity->name.' $'.$this->showController->entity->variableName, $contents);
        });
    }
}
