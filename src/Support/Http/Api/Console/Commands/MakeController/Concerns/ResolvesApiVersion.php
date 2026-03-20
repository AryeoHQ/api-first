<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController\Concerns;

use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Tooling\Composer\Composer;

use function Laravel\Prompts\select;

/**
 * @mixin \Illuminate\Console\GeneratorCommand
 * @mixin \Tooling\GeneratorCommands\Concerns\SearchesClasses
 */
trait ResolvesApiVersion
{
    use RetrievesApiVersionFromArgument;
    use RetrievesApiVersionFromOption;

    public const NEW_API_VERSION_OPTION = 'Create new API version';

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
            options: [...$options, self::NEW_API_VERSION_OPTION],
            required: true,
            scroll: 5,
            default: $options !== [] ? end($options) : null,
        );

        if ($selected === self::NEW_API_VERSION_OPTION) {
            return $this->getNextApiVersion();
        }

        return str($selected);
    }

    /**
     * @return array<array-key, string>
     */
    protected function getApiVersionOptions(): array
    {
        $composer = resolve(Composer::class);
        $composer->optimizeClassMap();

        return $composer->classMap->keys()
            ->filter(fn (string $fqcn): bool => Str::is('*\\Http\\Api\\V*\\*', $fqcn))
            ->map(fn (string $fqcn): string => Str::of($fqcn)->after('\\Http\\Api\\')->before('\\')->toString())
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    protected function getNextApiVersion(): Stringable
    {
        $latest = collect($this->getApiVersionOptions())
            ->map(fn (string $version): int => (int) Str::after($version, 'V'))
            ->max();

        return str('V'.(($latest ?? 0) + 1));
    }
}
