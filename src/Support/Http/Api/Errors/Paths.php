<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

final class Paths
{
    public function cover(Request $request): bool
    {
        return array_any(
            Arr::wrap(config('api-first.errors.paths', [])),
            fn (mixed $pattern): bool => is_string($pattern) && $request->is($pattern),
        );
    }
}
