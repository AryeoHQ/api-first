<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

use LogicException;
use Support\Http\Api\Preconditions\Contracts\Target;
use Support\Http\Api\Preconditions\Precondition as BasePrecondition;

final class Precondition extends BasePrecondition
{
    public function target(): Target
    {
        throw new LogicException('A rule fixture never resolves its target.');
    }
}
