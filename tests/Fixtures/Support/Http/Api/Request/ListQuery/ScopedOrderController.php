<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Request\ListQuery;

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
    public function __invoke(string $list): Collection
    {
        $query = match ($list) {
            'scoped' => ScopedOrderTestModel::query(),
            default => Rfc3339ExtendedTestModel::query()->orderBy('created_at'),
        };

        return new Collection($query->cursorPaginateByKey());
    }
}
