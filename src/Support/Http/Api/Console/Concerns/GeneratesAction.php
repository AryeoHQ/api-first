<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Concerns;

use Illuminate\Support\Stringable;
use Support\Http\Api\Console\Enums\ActionMethod;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\References\Controller;
use Symfony\Component\Console\Input\InputOption;

trait GeneratesAction
{
    private function resolveActionController(EndpointType $endpointType): Controller
    {
        $actionName = $this->resolveActionName();
        $actionMethod = $this->resolveActionMethod();

        return $this->buildController($this->entity, $endpointType, $actionName, $actionMethod);
    }

    private function resolveActionName(): Stringable
    {
        $name = when(
            $this->option('action'),
            fn ($action) => $action,
            fn () => \Laravel\Prompts\text(
                label: 'What is the name of the action? (ie: PayInvoice, Download, etc.)',
                required: true,
            ),
        );

        return str($name)->studly();
    }

    private function resolveActionMethod(): ActionMethod
    {
        return rescue(
            fn () => ActionMethod::from($this->option('action-method')),
            fn () => ActionMethod::from(
                \Laravel\Prompts\select(
                    label: 'What HTTP method should the action use?',
                    options: array_column(ActionMethod::cases(), 'value'),
                    default: ActionMethod::Post->value,
                    required: true,
                )
            ),
            false,
        );
    }

    /** @return array<int, InputOption> */
    private function getActionInputOptions(): array
    {
        return [
            new InputOption('action', null, InputOption::VALUE_OPTIONAL, 'The action name (e.g. PayInvoice).'),
            new InputOption('action-method', null, InputOption::VALUE_OPTIONAL, 'The action HTTP method (GET or POST).'),
        ];
    }
}
