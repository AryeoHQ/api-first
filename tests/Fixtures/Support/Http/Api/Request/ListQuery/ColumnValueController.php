<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Request\ListQuery;

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
    public function __invoke(): Collection
    {
        return new Collection(PagingTitledTestModel::query()->orderBy('title')->orderBy('id')->cursorPaginateByKey());
    }
}
