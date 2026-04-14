<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Authorizers;

use Support\Http\Authorizer as BaseAuthorizer;

final class Authorizer extends BaseAuthorizer
{
    public function authorize(): bool
    {
        return true;
    }
}
