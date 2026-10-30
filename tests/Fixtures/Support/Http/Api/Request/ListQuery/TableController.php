<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Request\ListQuery;

use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Fluent;
use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;

final class TableController
{
    #[Route(
        name: 'list-query-table',
        uri: 'list-query-table',
        methods: Method::Get,
    )]
    public function __invoke(Request $request): Collection
    {
        $paging = new Fluent($request->query->all());

        $rows = DB::table('rfc3339_extended_test_models')
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursorPaginate($paging->filled('paging.size') ? $paging->integer('paging.size') : 10);

        assert($rows instanceof CursorPaginator);

        return new Collection($rows->through(fn (object $row): Rfc3339ExtendedTestModel => (new Rfc3339ExtendedTestModel)->newFromBuilder((array) $row)));
    }
}
