<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PagingInformation;

use Closure;
use Illuminate\Http\Request;

class PagingInformation
{
    public function paginationInformation(): Closure
    {
        return function (Request $request, array $paginated, array $default) {
            return [
                'meta' => [
                    'paging' => Paging::from($paginated),
                ],
            ];
        };
    }
}
