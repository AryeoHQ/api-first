<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Uri;
use LogicException;
use Support\Http\Api\Pagination\CursorSignature;

final class DirectionalPaging
{
    /**
     * @param  array<array-key, mixed>  $paginated
     * @return array<string, mixed>|null
     */
    public static function from(Request $request, array $paginated): null|array
    {
        $before = self::sign($request, data_get($paginated, 'prev_cursor'));
        $after = self::sign($request, data_get($paginated, 'next_cursor'));

        return match ($before === null && $after === null) {
            true => null,
            false => [
                'before' => $before,
                'before_url' => self::url($request, 'before', $before),
                'after' => $after,
                'after_url' => self::url($request, 'after', $after),
                'size' => data_get($paginated, 'per_page'),
            ],
        };
    }

    private static function sign(Request $request, mixed $cursor): null|string
    {
        if (! is_string($cursor)) {
            return null;
        }

        $fingerprint = $request->attributes->get(CursorSignature::class);

        if (! is_string($fingerprint)) {
            throw new LogicException('A cursor needs the list fingerprint that cursorPaginateByKey() records on the request.');
        }

        return CursorSignature::sign($cursor, $fingerprint);
    }

    private static function url(Request $request, string $direction, null|string $cursor): null|string
    {
        if ($cursor === null) {
            return null;
        }

        $query = $request->query();
        $paging = is_array($query['paging'] ?? null) ? Arr::except($query['paging'], ['after', 'before']) : [];

        return (string) Uri::of($request->url())->replaceQuery([...$query, 'paging' => [...$paging, $direction => $cursor]]);
    }
}
