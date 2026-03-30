<?php

declare(strict_types=1);

namespace Tooling\Http\Api\Composer\ClassMap\Collectors;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tooling\Composer\ClassMap\Collectors\Contracts\Collector;
use Tooling\Composer\ClassMap\Collectors\Provides\Fakeable;

class ApiVersions implements Collector
{
    use Fakeable;

    /** @return \Illuminate\Support\Collection<int, class-string> */
    public function collect(Collection $classes): Collection
    {
        return $classes // @phpstan-ignore return.type
            ->filter(fn (string $class): bool => Str::is('*\\Http\\Api\\V*\\*', $class))
            ->map(fn (string $class): string => Str::of($class)->match('/^(.+\\\\Http\\\\Api\\\\V\d+)/')->toString())
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}
