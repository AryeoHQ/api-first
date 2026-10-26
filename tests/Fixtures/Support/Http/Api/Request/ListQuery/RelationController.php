<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Request\ListQuery;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;
use Tests\Fixtures\Support\Http\Api\Pagination\PagingOwnerTestModel;

final class RelationController
{
    #[Route(
        name: 'list-query-relation',
        uri: 'list-query-relation/{owner}/{relation}',
        methods: Method::Get,
    )]
    public function __invoke(string $owner, string $relation): RelationCollection
    {
        $owner = PagingOwnerTestModel::query()->findOrFail($owner);

        $query = match ($relation) {
            'members' => $owner->members()->orderBy('rfc3339_extended_test_models.created_at')->orderBy('rfc3339_extended_test_models.id'),
            default => $owner->items()->orderBy('paging_item_test_models.created_at')->orderBy('paging_item_test_models.id'),
        };

        return new RelationCollection($query->cursorPaginateByKey());
    }
}
