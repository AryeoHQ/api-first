<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Concerns;

use Support\Http\Api\Console\Enums\Endpoint;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\References\Controller;
use Symfony\Component\Console\Input\InputOption;

trait GeneratesRest
{
    private function resolveRestController(EndpointType $endpointType): Controller
    {
        $endpoint = rescue(
            fn () => Endpoint::from($this->option('endpoint')),
            fn () => Endpoint::from(
                \Laravel\Prompts\select(
                    label: 'What endpoint would you like to create?',
                    options: array_column(Endpoint::cases(), 'value'),
                    required: true,
                )
            ),
            false,
        );

        return $this->buildController($this->entity, $endpointType, $endpoint);
    }

    /** @return array<int, InputOption> */
    private function getRestInputOptions(): array
    {
        return [
            new InputOption('endpoint', null, InputOption::VALUE_OPTIONAL, 'The endpoint name (e.g. index, store).'),
        ];
    }
}
