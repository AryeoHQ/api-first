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
    #[\Override]
    protected function ensureParameterIsPrimitive(mixed $parameter): mixed
    {
        return $parameter instanceof DateTimeInterface
            ? $parameter->format(DateTimeInterface::RFC3339_EXTENDED)
            : parent::ensureParameterIsPrimitive($parameter);
    }
}
