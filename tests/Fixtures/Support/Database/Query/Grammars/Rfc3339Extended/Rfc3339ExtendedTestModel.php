<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Support\Entities\Contracts\Entity;

// @phpstan-ignore entities.Model.CollectedBy.required, entities.Model.UseEloquentBuilder.required, entities.Model.UseFactory.required, entities.Model.UsePolicy.required, entities.Model.HasFactory.required
class Rfc3339ExtendedTestModel extends Model implements Entity
{
    use HasUuids;

    protected $table = 'rfc3339_extended_test_models';
}
