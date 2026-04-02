<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/** @implements CastsAttributes<Sort, Sort|mixed> */
class SortCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes): null|Sort
    {
        if ($value === null) {
            return null;
        }

        return new Sort($value);
    }

    public function set($model, $key, $value, $attributes): null|string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Sort) {
            return (string) $value;
        }

        return (string) new Sort($value);
    }
}
