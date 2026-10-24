<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Database\Eloquent\Model;
use Support\Database\Eloquent\Concurrency\Contracts\Revisioned;
use Support\Http\Api\Preconditions\Contracts\Target;
use Support\Http\Api\Preconditions\Precondition as BasePrecondition;
use Support\Http\Api\Preconditions\Targets\EntityTarget;
use Tests\Fixtures\Support\Schemas\ApiVersion;

final class Precondition extends BasePrecondition
{
    public function target(): Target
    {
        /** @var Model&Revisioned $note */
        $note = $this->route('note');

        return EntityTarget::for($note, ApiVersion::V1)->comparingSelf();
    }
}
