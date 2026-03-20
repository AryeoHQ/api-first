<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PagingInformation;

use Closure;
use Illuminate\Http\Request;

class PagingInformation
{
    public function paginationInformation(): Closure
    {
        $paging = new Paging;
        $filters = new Filters;
        $sort = new Sort;

        return function (Request $request, array $paginated, array $default) use ($paging, $filters, $sort) {
            $resolvedFilters = $filters($request);
            $resolvedSort = $sort($request);

            return [
                'meta' => [
                    'paging' => $paging($paginated),
                    'filters' => $resolvedFilters !== [] ? $resolvedFilters : null,
                    'sort' => $resolvedSort,
                ],
            ];
        };
    }
}
