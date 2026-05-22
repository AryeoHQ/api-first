<?php

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan;

use Illuminate\Database\Eloquent\Model;
use Support\Entities\Contracts\Entity;

class EntityWithoutLoggable extends Model implements Entity {}
