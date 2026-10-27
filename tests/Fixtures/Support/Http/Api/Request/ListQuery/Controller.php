<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Request\ListQuery;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;

final class Controller
{
    #[Route(
        name: 'list-query',
        uri: 'list-query/{model}',
        methods: Method::Get,
    )]
    public function __invoke(Validator $validator, string $model): Collection
    {
        $direction = $validator->query('sort') === '-created_at' ? 'desc' : 'asc';

        $query = match ($model) {
            'unordered' => Rfc3339ExtendedTestModel::query(),
            default => Rfc3339ExtendedTestModel::query()->orderBy('created_at', $direction)->orderBy('id', $direction),
        };

        return new Collection($query->cursorPaginateByKey());
    }
}
