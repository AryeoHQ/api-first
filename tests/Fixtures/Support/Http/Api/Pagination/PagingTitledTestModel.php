<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Pagination;

use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;

class PagingTitledTestModel extends Rfc3339ExtendedTestModel // @phpstan-ignore entities.Model.CollectedBy.required, entities.Model.UseEloquentBuilder.required, entities.Model.UseFactory.required, entities.Model.UsePolicy.required
{
    protected $table = 'paging_titled_test_models';
}
