<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController\Concerns;

use Support\Http\Api\Console\Enums\Endpoint;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\References\Controller;

trait GeneratesRest
{
    private function resolveRestController(EndpointType $endpointType): Controller
    {
        $endpoint = Endpoint::from(
            \Laravel\Prompts\select(
                label: 'What endpoint would you like to create?',
                options: array_column(Endpoint::cases(), 'value'),
                required: true,
            )
        );

        return $this->buildController($this->entity, $endpointType, $endpoint, scope: $endpoint->scope());
    }
}
