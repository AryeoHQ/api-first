<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Concerns;

use Illuminate\Support\Stringable;
use Support\Http\Api\Console\Enums\ActionMethod;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\Console\Enums\Scope;
use Support\Http\Api\References\Controller;

trait GeneratesAction
{
    private function resolveActionController(EndpointType $endpointType): Controller
    {
        $actionName = $this->resolveActionName();
        $actionMethod = $this->resolveActionMethod();
        $scope = $this->resolveScope();

        return $this->buildController($this->entity, $endpointType, $actionName, $actionMethod, $scope);
    }

    private function resolveActionName(): Stringable
    {
        return str(\Laravel\Prompts\text(
            label: 'What is the name of the action? (ie: PayInvoice, Download, etc.)',
            required: true,
        ))->studly();
    }

    private function resolveActionMethod(): ActionMethod
    {
        return ActionMethod::from(
            \Laravel\Prompts\select(
                label: 'What HTTP method should the action use?',
                options: array_column(ActionMethod::cases(), 'value'),
                default: ActionMethod::Post->value,
                required: true,
            )
        );
    }

    private function resolveScope(): Scope
    {
        return Scope::from(
            \Laravel\Prompts\select(
                label: 'What is the scope of the action?',
                options: array_column(Scope::cases(), 'value'),
                default: Scope::Instance->value,
                required: true,
            )
        );
    }
}
