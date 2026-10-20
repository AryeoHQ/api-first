<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

final class WithoutAuthorizerController
{
    public function __invoke(Precondition $precondition, Validator $validator): void
    {
        //
    }
}
