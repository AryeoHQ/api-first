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
            'before_url' => data_get($paginated, 'prev_page_url'),
            'after' => $after,
            'after_url' => data_get($paginated, 'next_page_url'),
            'size' => data_get($paginated, 'per_page'),
        ];
    }
}
