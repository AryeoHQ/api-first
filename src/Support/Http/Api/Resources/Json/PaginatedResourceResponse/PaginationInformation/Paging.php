<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation;

use Illuminate\Support\Uri;

final class Paging
{
    /**
     * @param  array<array-key, mixed>  $paginated
     * @return array<string, mixed>|null
     */
    public static function from(array $paginated): null|array
    {
        $before = data_get($paginated, 'prev_cursor');
        $after = data_get($paginated, 'next_cursor');

        return match ($before === null && $after === null) {
            true => null,
            false => [
                'before' => $before,
                'before_url' => self::rewriteCursorParam(data_get($paginated, 'prev_page_url')),
                'after' => $after,
                'after_url' => self::rewriteCursorParam(data_get($paginated, 'next_page_url')),
                'size' => data_get($paginated, 'per_page'),
            ],
        };
    }

    private static function rewriteCursorParam(mixed $url): null|string
    {
        if ($url === null) {
            return null;
        }

        $uri = Uri::of($url);

        return (string) $uri->when(
            $uri->query()->get('cursor'),
            fn (Uri $uri, string $cursor) => $uri
                ->withoutQuery('cursor')
                ->withQuery(['paging' => ['cursor' => $cursor]])
        );
    }
}
