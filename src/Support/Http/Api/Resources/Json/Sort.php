<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json;

use Illuminate\Http\Request;
use Stringable;
use Support\Http\Requests\Contracts\CastableData;

final class Sort
{
    public static function from(Request $request): null|string
    {
        /** @var ?CastableData $castable */
        $castable = app()->bound(CastableData::class) ? app(CastableData::class) : null;

        $sort = $castable?->sort ?? $request->query('sort'); // @phpstan-ignore property.notFound, nullsafe.neverNull

        return match (true) {
            $sort instanceof Stringable => (string) $sort,
            is_string($sort) => $sort,
            default => null,
        };
    }
}
