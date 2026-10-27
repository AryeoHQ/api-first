<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Request\ListQuery;

use Illuminate\Http\Request;
use Illuminate\Support\Fluent;
use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;
use Tests\Fixtures\Support\Http\Api\Pagination\ScopedOrderTestModel;

final class ScopedOrderController
{
    #[Route(
        name: 'list-query-scoped-order',
        uri: 'list-query-scoped-order/{list}',
        methods: Method::Get,
    )]
    public function __invoke(Request $request, string $list): Collection
    {
        $paging = new Fluent($request->query->all());

        $query = match ($list) {
            'scoped' => ScopedOrderTestModel::query(),
            default => Rfc3339ExtendedTestModel::query()->orderBy('created_at'),
        };

        return new Collection($query->cursorPaginate($paging->filled('paging.size') ? $paging->integer('paging.size') : 10));
    }
}
