<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

final class AfterValidatorController
{
    public function __invoke(
        Authorizer $authorizer,
        Validator $validator,
        null|Precondition $precondition,
    ): void {
        //
    }
}
