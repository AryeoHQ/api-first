<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController\Concerns;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Stringable;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @mixin GeneratorCommand
 */
trait RetrievesApiVersionFromArgument
{
    protected function apiVersionFromArgument(): null|Stringable
    {
        return match ($this->hasArgument('api-version')) {
            false => null,
            default => match (($provided = str($this->argument('api-version')))->isNotEmpty()) { // @phpstan-ignore argument.type, larastan.console.undefinedArgument
                true => $provided,
                default => null,
            },
        };
    }

    /** @return array<int, InputArgument> */
    protected function getApiVersionInputArguments(): array
    {
        return [
            new InputArgument('api-version', InputArgument::OPTIONAL, 'The API version (e.g. V1).'),
        ];
    }
}
