<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Support\Http\Api\Preconditions\Contracts\Target;
use Support\Http\Api\Preconditions\Precondition as BasePrecondition;
use Support\Http\Api\Preconditions\Targets\EntityTarget;
use Tests\Fixtures\Support\Schemas\ApiVersion;

final class ChildrenOrderPrecondition extends BasePrecondition
{
    protected bool $required = true;

    public function target(): Target
    {
        /** @var Note $note */
        $note = $this->route('note');

        return EntityTarget::for($note, ApiVersion::V1)->comparingNamed(NoteRevision::ChildrenOrder);
    }
}
