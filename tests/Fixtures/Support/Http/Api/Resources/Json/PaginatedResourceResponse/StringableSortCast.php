<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/** @implements CastsAttributes<StringableSort, StringableSort|mixed> */
class StringableSortCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes): null|StringableSort
    {
        if ($value === null) {
            return null;
        }

        return new StringableSort($value);
    }

    public function set($model, $key, $value, $attributes): null|string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof StringableSort) {
            return (string) $value;
        }

        return (string) new StringableSort($value);
    }
}
