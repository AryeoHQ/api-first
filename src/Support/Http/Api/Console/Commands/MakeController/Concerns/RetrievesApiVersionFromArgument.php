<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController\Concerns;

use Illuminate\Support\Stringable;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @mixin \Illuminate\Console\GeneratorCommand
 */
trait RetrievesApiVersionFromArgument
{
    protected function apiVersionFromArgument(): null|Stringable
    {
        if (! $this->hasArgument('api-version')) {
            return null;
        }

        $provided = str($this->argument('api-version')); // @phpstan-ignore argument.type, larastan.console.undefinedArgument

        if ($provided->isEmpty()) {
            return null;
        }

        return $provided;
    }

    /** @return array<int, InputArgument> */
    protected function getApiVersionInputArguments(): array
    {
        return [
            new InputArgument('api-version', InputArgument::OPTIONAL, 'The API version (e.g. V1).'),
        ];
    }
}
