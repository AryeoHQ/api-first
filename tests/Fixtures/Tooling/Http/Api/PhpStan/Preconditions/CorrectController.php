<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

final class CorrectController
{
    public function __invoke(Authorizer $authorizer, Precondition $precondition, Validator $validator, \stdClass $model): void
    {
        //
    }
}
