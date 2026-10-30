<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

use Support\Http\Authorizer as BaseAuthorizer;

final class Authorizer extends BaseAuthorizer
{
    public function authorize(): bool
    {
        return true;
    }
}
