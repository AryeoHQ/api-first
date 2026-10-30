<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;

final class CreateController
{
    #[Route(
        name: 'api.v1.widgets.store',
        uri: 'api/v1/widgets',
        methods: Method::Post,
    )]
    public function __invoke(Authorizer $authorizer, Validator $validator): void
    {
        //
    }
}
