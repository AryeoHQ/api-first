<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Support\Http\Authorizer as BaseAuthorizer;

final class DeniedAuthorizer extends BaseAuthorizer
{
    public function authorize(): bool
    {
        return false;
    }
}
