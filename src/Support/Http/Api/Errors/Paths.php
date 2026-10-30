<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class Paths
{
    public function cover(Request $request): bool
    {
        return array_any(
            Arr::wrap(config('api-first.errors.paths', [])),
            fn (mixed $pattern): bool => is_string($pattern) && $this->matches($pattern, $request),
        );
    }

    private function matches(string $pattern, Request $request): bool
    {
        $host = Str::before($pattern, '/');

        if (! str_contains($host, '.')) {
            return $request->is($pattern);
        }

        return Str::is($host, $request->getHost()) && $request->is(Str::after($pattern, '/'));
    }
}
