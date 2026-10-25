<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

final class BeforeAuthorizerController
{
    public function __invoke(
        Precondition $precondition,
        Authorizer $authorizer,
        Validator $validator,
    ): void {
        //
    }
}
