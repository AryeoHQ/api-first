<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController\Concerns;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Tooling\Composer\ClassMap\Cache;
use Tooling\Http\Api\Composer\ClassMap\Collectors\ApiVersions;

use function Laravel\Prompts\select;

/**
 * @mixin GeneratorCommand
 */
trait ResolvesApiVersion
{
    use RetrievesApiVersionFromArgument;
    use RetrievesApiVersionFromOption;

    public const CREATE_NEW_VERSION = 'Create new API version';

    public protected(set) Stringable $apiVersion;

    public function retrieveApiVersion(): Stringable
    {
        return $this->apiVersionFromOption() ?? $this->apiVersionFromArgument() ?? $this->apiVersionFromPrompt();
    }

    public function resolveApiVersion(): void
    {
        $this->apiVersion = $this->retrieveApiVersion();
    }

    public function apiVersionFromPrompt(): Stringable
    {
        $options = $this->getApiVersionOptions();

        $selected = select(
            label: 'What is the API version?',
            options: [...$options, self::CREATE_NEW_VERSION],
            required: true,
            scroll: 5,
            default: $options !== [] ? end($options) : null,
        );

        return match ($selected) {
            self::CREATE_NEW_VERSION => $this->getNextApiVersion(),
            default => str($selected),
        };
    }

    /**
     * @return array<array-key, string>
     */
    protected function getApiVersionOptions(): array
    {
        return collect(resolve(Cache::class)->get(ApiVersions::class) ?? [])
            ->map(fn (string $namespace): string => Str::of($namespace)->after('\\Http\\Api\\')->toString())
            ->values()
            ->toArray();
    }

    protected function getNextApiVersion(): Stringable
    {
        $latest = collect($this->getApiVersionOptions())
            ->map(fn (string $version): int => (int) Str::after($version, 'V'))
            ->max() ?? 0;

        return str('V'.($latest + 1));
    }
}
