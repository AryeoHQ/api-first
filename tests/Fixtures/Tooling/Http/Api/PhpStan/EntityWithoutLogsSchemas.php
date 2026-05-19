<?php

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan;

use Illuminate\Database\Eloquent\Model;
use Support\Entities\Contracts\Entity;
use Support\Events\Log\Contracts\Loggable;

class EntityWithoutLogsSchemas extends Model implements Entity, Loggable
{
    public function toLoggable(): iterable
    {
        return [];
    }
}
