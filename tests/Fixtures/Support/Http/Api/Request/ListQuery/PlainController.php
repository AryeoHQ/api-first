<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Request\ListQuery;

use Illuminate\Http\Request;
use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;

final class PlainController
{
    #[Route(
        name: 'list-query-plain',
        uri: 'list-query-plain/{model}',
        methods: Method::Get,
    )]
    public function __invoke(Request $request, string $model): Collection
    {
        $direction = $request->query('sort') === '-created_at' ? 'desc' : 'asc';

        $query = match ($model) {
            'unordered' => Rfc3339ExtendedTestModel::query(),
            default => Rfc3339ExtendedTestModel::query()->orderBy('created_at', $direction)->orderBy('id', $direction),
        };

        return new Collection($query->cursorPaginateByKey());
    }
}
