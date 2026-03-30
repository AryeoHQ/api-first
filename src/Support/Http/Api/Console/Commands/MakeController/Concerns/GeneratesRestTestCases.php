<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController\Concerns;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Support\Http\Api\Console\Enums\Endpoint;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\References\Controller;
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
                $this->showController->entity->name.' $'.$this->showController->entity->variableName, $contents
            );
        });
    }
}
