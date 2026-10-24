<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Errors;

use Support\Http\Api\Errors\Attributes\LegacyErrors;
use Support\Http\Api\Errors\Exceptions\PreconditionFailed;

#[LegacyErrors]
final class LegacyErrorsController
{
    public function validate(Validator $validator): void
    {
        //
    }

    public function preconditionFailed(): never
    {
        throw new PreconditionFailed;
    }
}
