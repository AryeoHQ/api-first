<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Rfc3339ExtendedTestModel extends Model
{
    use HasUuids;

    protected $table = 'rfc3339_extended_test_models';
}
