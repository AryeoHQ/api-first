<?php

declare(strict_types=1);

namespace Support\Http\Api\Pagination;

use DateTimeInterface;
use Illuminate\Pagination\CursorPaginator as UpstreamCursorPaginator;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends UpstreamCursorPaginator<TKey, TValue>
 */
final class CursorPaginator extends UpstreamCursorPaginator
{
    protected function ensureParameterIsPrimitive(mixed $parameter): mixed
    {
        if ($parameter instanceof DateTimeInterface) {
            return $parameter->format(DateTimeInterface::RFC3339_EXTENDED);
        }

        return parent::ensureParameterIsPrimitive($parameter);
    }
}
