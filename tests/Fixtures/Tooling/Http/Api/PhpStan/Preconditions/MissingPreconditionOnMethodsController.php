<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;

final class MissingPreconditionOnMethodsController
{
    #[Route(
        name: 'api.v1.widgets.replace',
        uri: 'api/v1/widgets/{widget}',
        methods: [Method::Put, Method::Delete],
    )]
    public function __invoke(Authorizer $authorizer, \stdClass $model): void
    {
        //
    }
}
