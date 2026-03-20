<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PagingInformation;

final class Paging
{
    /**
     * @param  array<array-key, mixed>  $paginated
     * @return array<string, mixed>|null
     */
    public function __invoke(array $paginated): null|array
    {
        $before = data_get($paginated, 'prev_cursor');
        $after = data_get($paginated, 'next_cursor');

        if ($before === null && $after === null) {
            return null;
        }

        return [
            'before' => $before,
            'before_url' => $this->rewriteCursorParam(data_get($paginated, 'prev_page_url')),
            'after' => $after,
            'after_url' => $this->rewriteCursorParam(data_get($paginated, 'next_page_url')),
            'size' => data_get($paginated, 'per_page'),
        ];
    }

    private function rewriteCursorParam(mixed $url): null|string
    {
        if ($url === null) {
            return null;
        }

        $parsed = parse_url($url);
        parse_str($parsed['query'] ?? '', $query);

        if (array_key_exists('cursor', $query)) {
            $query['paging']['cursor'] = $query['cursor'];
            unset($query['cursor']);
        }

        $base = $parsed['scheme'].'://'.$parsed['host'].($parsed['path'] ?? '');

        return $base.'?'.http_build_query($query);
    }
}
