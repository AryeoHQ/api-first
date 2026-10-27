<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Request\ListQuery;

use Illuminate\Http\Request;
use Illuminate\Support\Fluent;
use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;
use Tests\Fixtures\Support\Http\Api\Pagination\PagingTitledTestModel;

final class ColumnValueController
{
    #[Route(
        name: 'list-query-values',
        uri: 'list-query-values/{model}',
        methods: Method::Get,
    )]
    public function __invoke(Request $request): Collection
    {
        $paging = new Fluent($request->query->all());

        return new Collection(PagingTitledTestModel::query()->orderBy('title')->orderBy('id')->cursorPaginate($paging->filled('paging.size') ? $paging->integer('paging.size') : 10));
    }
}
