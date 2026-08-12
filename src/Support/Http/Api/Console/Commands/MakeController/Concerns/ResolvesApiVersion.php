<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController\Concerns;

use Illuminate\Support\Stringable;
use Support\Http\Resources\Schemas\Console\Concerns\ResolvesSchemaVersion;

trait ResolvesApiVersion
{
    use ResolvesSchemaVersion;

    public Stringable $apiVersion {
        get => str($this->version->name);
    }

    protected function schemaVersionOptionName(): string
    {
        return 'api-version';
    }

    public function resolveApiVersion(): void
    {
        $this->resolveVersion();
    }

    /** @return array<int, \Symfony\Component\Console\Input\InputOption> */
    protected function getApiVersionInputOptions(): array
    {
        return $this->getSchemaVersionInputOptions();
    }
}
