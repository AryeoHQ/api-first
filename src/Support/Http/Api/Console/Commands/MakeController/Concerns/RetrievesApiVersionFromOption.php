<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController\Concerns;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Stringable;
use Symfony\Component\Console\Input\InputOption;

/**
 * @mixin GeneratorCommand
 */
trait RetrievesApiVersionFromOption
{
    protected function apiVersionFromOption(): null|Stringable
    {
        return match ($this->hasOption('api-version')) {
            false => null,
            default => match (($provided = str($this->option('api-version')))->isNotEmpty()) {
                true => $provided,
                default => null,
            },
        };
    }

    /** @return array<int, InputOption> */
    protected function getApiVersionInputOptions(): array
    {
        return [
            new InputOption('api-version', null, InputOption::VALUE_REQUIRED, 'The API version (e.g. V1).'),
        ];
    }
}
