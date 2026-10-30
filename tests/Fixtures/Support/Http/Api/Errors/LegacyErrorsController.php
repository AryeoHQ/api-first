<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Errors;

use Support\Http\Api\Errors\Attributes\LegacyErrors;
use Support\Http\Api\Errors\Exceptions\PreconditionFailed;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

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

    public function symfonyPreconditionFailed(): never
    {
        throw new PreconditionFailedHttpException(headers: ['ETag' => '"33a64df5"']);
    }

    public function abort(int $status): never
    {
        abort($status, 'Tenant A owns secret X.');
    }
}
