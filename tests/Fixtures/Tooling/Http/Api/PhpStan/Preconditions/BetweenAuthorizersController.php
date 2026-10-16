<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

final class BetweenAuthorizersController
{
    public function __invoke(
        Authorizer $authorizer,
        Precondition $precondition,
        Authorizer $other,
    ): void {
        //
    }
}
