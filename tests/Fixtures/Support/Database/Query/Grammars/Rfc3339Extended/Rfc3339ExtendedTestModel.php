<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UseFactory(Rfc3339ExtendedTestModelFactory::class)]
class Rfc3339ExtendedTestModel extends Model // @phpstan-ignore entities.Model.Entity.required
{
    /** @use HasFactory<Rfc3339ExtendedTestModelFactory> */
    use HasFactory;
    use HasUuids;

    protected $table = 'rfc3339_extended_test_models';
}
