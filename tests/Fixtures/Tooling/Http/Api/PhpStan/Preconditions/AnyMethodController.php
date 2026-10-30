<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;

final class AnyMethodController
{
    #[Route(
        name: 'api.v1.widgets.any',
        uri: 'api/v1/widgets/{widget}',
        methods: Method::Any,
    )]
    public function __invoke(Authorizer $authorizer, Validator $validator, \stdClass $model): void
    {
        //
    }
}
