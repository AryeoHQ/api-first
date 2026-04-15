<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Provides;

use Illuminate\Http\Request;
use Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Filters;
use Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Paging;
use Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Sort;

trait WithStructuredMeta
{
    /**
     * @param  array<string, mixed>  $paginated
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => [
                'filters' => Filters::from($request),
                'paging' => Paging::from($paginated),
                'sort' => Sort::from($request),
            ],
        ];
    }
}
