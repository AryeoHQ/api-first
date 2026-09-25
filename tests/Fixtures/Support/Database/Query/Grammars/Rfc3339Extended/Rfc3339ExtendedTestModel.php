<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Support\Entities\Contracts\Entity;

#[UseFactory(Rfc3339ExtendedTestModelFactory::class)]
class Rfc3339ExtendedTestModel extends Model implements Entity // @phpstan-ignore entities.Model.CollectedBy.required, entities.Model.UseEloquentBuilder.required, entities.Model.UsePolicy.required
{
    /** @use HasFactory<Rfc3339ExtendedTestModelFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'rfc3339_extended_test_models';
}
