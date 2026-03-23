<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController\Concerns;

use PHPUnit\Framework\Attributes\Test;
use Support\Http\Api\Console\Enums\ActionMethod;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\Console\Enums\Scope;
use Support\Http\Api\References\Controller;
use Support\Http\Api\References\Route;
use Tests\TestCase;

/**
 * @mixin TestCase
 */
trait GeneratesActionTestCases
{
    private Controller $actionController {
        get => Controller::make(
            Route::make('V1', $this->entity, EndpointType::Action, 'PayInvoice', ActionMethod::Post),
        );
    }

    private Controller $resourceActionController {
        get => Controller::make(
            Route::make('V1', $this->entity, EndpointType::Action, 'ExportOrders', ActionMethod::Post, Scope::Resource),
        );
    }

    #[Test]
    public function it_creates_an_instance_scoped_action_endpoint(): void
    {
        $input = [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => 'Action',
        ];

        $this->artisan($this->command, $input)->expectsQuestion(
            'What is the name of the action? (ie: PayInvoice, Download, etc.)',
            'PayInvoice'
        )->expectsChoice(
            'What HTTP method should the action use?',
            ActionMethod::Post->value, array_column(ActionMethod::cases(), 'value')
        )->expectsChoice(
            'What is the scope of the action?',
            Scope::Instance->value, array_column(Scope::cases(), 'value')
        )->assertSuccessful();

        $this->assertFileExists($this->actionController->filePath->toString());
        $this->assertFileExists($this->actionController->authorizer->filePath->toString());
        $this->assertFileExists($this->actionController->validator->filePath->toString());
        $this->assertFileExists($this->actionController->test->filePath->toString());

        tap(file_get_contents($this->actionController->filePath->toString()), function (string $contents): void {
            $this->assertStringContainsString($this->actionController->route->routeName->toString(), $contents);
            $this->assertStringContainsString($this->actionController->route->uri->toString(), $contents);
            $this->assertStringContainsString('Method::'.$this->actionController->route->method->name, $contents);
            $this->assertStringContainsString($this->actionController->entity->fqcn->toString(), $contents);
            $this->assertStringContainsString(
                $this->actionController->entity->name.' $'.$this->actionController->entity->variableName,
                $contents
            );
        });
    }

    #[Test]
    public function it_creates_a_resource_scoped_action_endpoint(): void
    {
        $input = [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => 'Action',
        ];

        $this->artisan($this->command, $input)->expectsQuestion(
            'What is the name of the action? (ie: PayInvoice, Download, etc.)',
            'ExportOrders'
        )->expectsChoice(
            'What HTTP method should the action use?',
            ActionMethod::Post->value, array_column(ActionMethod::cases(), 'value')
        )->expectsChoice(
            'What is the scope of the action?',
            Scope::Resource->value, array_column(Scope::cases(), 'value')
        )->assertSuccessful();

        $this->assertFileExists($this->resourceActionController->filePath->toString());

        tap(file_get_contents($this->resourceActionController->filePath->toString()), function (string $contents): void {
            $this->assertStringContainsString($this->resourceActionController->route->routeName->toString(), $contents);
            $this->assertStringContainsString($this->resourceActionController->route->uri->toString(), $contents);
            $this->assertStringContainsString('Method::'.$this->resourceActionController->route->method->name, $contents);
            $this->assertStringNotContainsString($this->resourceActionController->entity->fqcn->toString(), $contents);
            $this->assertStringNotContainsString(
                $this->resourceActionController->entity->name.' $'.$this->resourceActionController->entity->variableName,
                $contents
            );
        });
    }
}
