<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;

final class ResourceActionController
{
    #[Route(
        name: 'api.v1.widgets.actions.export',
        uri: 'api/v1/widgets/actions/export',
        methods: Method::Post,
    )]
    public function __invoke(Authorizer $authorizer, Validator $validator): void
    {
        //
    }
}
