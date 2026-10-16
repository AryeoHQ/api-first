<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Database\Eloquent\Model;
use Support\Http\Api\Preconditions\Contracts\Target;
use Support\Http\Api\Preconditions\Precondition as BasePrecondition;
use Support\Http\Api\Preconditions\Targets\EntityTarget;
use Support\Http\Resources\Schemas\Contracts\Schemable;
use Tests\Fixtures\Support\Schemas\ApiVersion;

final class Precondition extends BasePrecondition
{
    public function target(): Target
    {
        /** @var Model&Schemable $note */
        $note = $this->route('note');

        return new EntityTarget($note, ApiVersion::V1);
    }
}
