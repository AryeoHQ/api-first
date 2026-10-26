<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Request\ListQuery;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;
use Tests\Fixtures\Support\Http\Api\Pagination\PagingTitledTestModel;
use Tests\Fixtures\Support\Http\Api\Pagination\ZuluDateFormatTestModel;

final class ColumnValueController
{
    #[Route(
        name: 'list-query-values',
        uri: 'list-query-values/{model}',
        methods: Method::Get,
    )]
    public function __invoke(string $model): Collection
    {
        $query = match ($model) {
            'titled' => PagingTitledTestModel::query()->orderBy('title')->orderBy('id'),
            default => ZuluDateFormatTestModel::query()->orderBy('created_at')->orderBy('id'),
        };

        return new Collection($query->cursorPaginateByKey());
    }
}
