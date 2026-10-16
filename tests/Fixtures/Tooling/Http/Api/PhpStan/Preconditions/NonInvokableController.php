<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

final class NonInvokableController
{
    public function handle(Validator $validator, Precondition $precondition): void
    {
        //
    }
}
