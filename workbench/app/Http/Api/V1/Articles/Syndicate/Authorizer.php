<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles\Syndicate;

use Support\Http\Authorizer as BaseAuthorizer;

final class Authorizer extends BaseAuthorizer
{
    public function authorize(): bool
    {
        return true;
    }
}
