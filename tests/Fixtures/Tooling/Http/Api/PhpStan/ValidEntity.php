<?php

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan;

use Illuminate\Database\Eloquent\Model;
use Support\Entities\Contracts\Entity;
use Support\Entities\Models\Concerns\LogsSchemas;
use Support\Events\Log\Contracts\Loggable;
use Support\Http\Resources\Schemas\Contracts\Schemable;

class ValidEntity extends Model implements Entity, Loggable, Schemable
{
    use LogsSchemas;
}
