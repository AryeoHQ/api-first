<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController\Concerns;

use Illuminate\Support\Stringable;
use Symfony\Component\Console\Input\InputOption;

/**
 * @mixin \Illuminate\Console\GeneratorCommand
 */
trait RetrievesApiVersionFromOption
{
    protected function apiVersionFromOption(): null|Stringable
    {
        if (! $this->hasOption('api-version')) {
            return null;
        }

        $provided = str($this->option('api-version')); // @phpstan-ignore argument.type

        if ($provided->isEmpty()) {
            return null;
        }

        return $provided;
    }

    /** @return array<int, InputOption> */
    protected function getApiVersionInputOptions(): array
    {
        return [
            new InputOption('api-version', null, InputOption::VALUE_REQUIRED, 'The API version (e.g. V1).'),
        ];
    }
}
