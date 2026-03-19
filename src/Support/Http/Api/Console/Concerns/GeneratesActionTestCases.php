<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Concerns;

use PHPUnit\Framework\Attributes\Test;
use Support\Http\Api\Console\Enums\ActionMethod;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\References\Controller;
use Support\Http\Api\References\Route;

/**
 * @mixin \Tests\TestCase
 */
trait GeneratesActionTestCases
{
    private Controller $actionController {
        get => Controller::make(
            Route::make('V1', $this->entity, EndpointType::Action, 'PayInvoice', ActionMethod::Post),
        );
    }

    #[Test]
    public function it_prompts_for_action_method_when_option_is_invalid(): void
    {
        $input = [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => 'Action',
            '--action' => 'PayInvoice',
            '--action-method' => 'invalid',
        ];

        $this->artisan($this->command, $input)
            ->expectsChoice('What HTTP method should the action use?', ActionMethod::Post->value, array_column(ActionMethod::cases(), 'value'))
            ->assertSuccessful();

        $this->assertFileExists($this->actionController->filePath->toString());

        $contents = file_get_contents($this->actionController->filePath->toString());

        $this->assertStringContainsString('Method::'.$this->actionController->route->method->name, $contents);
    }

    #[Test]
    public function it_creates_an_action_endpoint(): void
    {
        $input = [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
            '--type' => 'Action',
            '--action' => 'PayInvoice',
            '--action-method' => 'POST',
        ];

        $this->artisan($this->command, $input)->assertSuccessful();

        $this->assertFileExists($this->actionController->filePath->toString());
        $this->assertFileExists($this->actionController->authorizer->filePath->toString());
        $this->assertFileExists($this->actionController->validator->filePath->toString());
        $this->assertFileExists($this->actionController->test->filePath->toString());

        $contents = file_get_contents($this->actionController->filePath->toString());

        $this->assertStringContainsString($this->actionController->route->routeName->toString(), $contents);
        $this->assertStringContainsString($this->actionController->route->uri->toString(), $contents);
        $this->assertStringContainsString('Method::'.$this->actionController->route->method->name, $contents);
        $this->assertStringContainsString($this->actionController->entity->fqcn->toString(), $contents);
        $this->assertStringContainsString($this->actionController->entity->name.' $'.$this->actionController->entity->variableName, $contents);
    }
}
