<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Request\ListQuery;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;

final class CastingController
{
    #[Route(
        name: 'list-query-casting',
        uri: 'list-query-casting',
        methods: Method::Get,
    )]
    public function __invoke(CastingValidator $validator): Collection
    {
        return new Collection($validator->paginate(Rfc3339ExtendedTestModel::query()->orderBy('created_at')->orderBy('id')));
    }
}
