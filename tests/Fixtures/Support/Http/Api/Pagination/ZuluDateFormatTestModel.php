<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Pagination;

use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;

class ZuluDateFormatTestModel extends Rfc3339ExtendedTestModel // @phpstan-ignore entities.Model.CollectedBy.required, entities.Model.UseEloquentBuilder.required, entities.Model.UseFactory.required, entities.Model.UsePolicy.required
{
    protected $dateFormat = 'Y-m-d\TH:i:s.v\Z';
}
