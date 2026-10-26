<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

final class UnionAfterValidatorController
{
    public function __invoke(
        Authorizer $authorizer,
        Validator $validator,
        \stdClass|Precondition $precondition,
    ): void {
        //
    }
}
