<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Uri;

final class Paging
{
    /**
     * @param  array<array-key, mixed>  $paginated
     * @return array<string, mixed>|null
     */
    public static function from(Request $request, array $paginated): null|array
    {
        $before = data_get($paginated, 'prev_cursor');
        $after = data_get($paginated, 'next_cursor');

        return match ($before === null && $after === null) {
            true => null,
            false => [
                'before' => $before,
                'before_url' => self::rewriteCursorParam($request, data_get($paginated, 'prev_page_url')),
                'after' => $after,
                'after_url' => self::rewriteCursorParam($request, data_get($paginated, 'next_page_url')),
                'size' => data_get($paginated, 'per_page'),
            ],
        };
    }

    private static function rewriteCursorParam(Request $request, mixed $url): null|string
    {
        if ($url === null) {
            return null;
        }

        $uri = Uri::of($url);

        return (string) $uri->when(
            $uri->query()->get('cursor'),
            fn (Uri $uri, string $cursor) => $uri->replaceQuery(array_replace_recursive(
                $request->query(),
                Arr::except($uri->query()->all(), 'cursor'),
                ['paging' => ['cursor' => $cursor]],
            ))
        );
    }
}
