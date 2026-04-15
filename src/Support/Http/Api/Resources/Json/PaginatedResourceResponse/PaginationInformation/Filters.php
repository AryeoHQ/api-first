<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation;

use Illuminate\Http\Request;
use Support\Http\Requests\Contracts\CastableData;

final class Filters
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Request $request): null|array
    {
        /** @var ?CastableData $castable */
        $castable = app()->bound(CastableData::class) ? app(CastableData::class) : null;

        $filters = $castable?->filters ?? $request->input('filters'); // @phpstan-ignore property.notFound, nullsafe.neverNull

        return is_array($filters) ? $filters : null;
    }
}
