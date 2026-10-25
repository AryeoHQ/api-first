<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;

final class CorrectController
{
    #[Route(
        name: 'api.v1.widgets.update',
        uri: 'api/v1/widgets/{widget}',
        methods: Method::Patch,
    )]
    public function __invoke(Authorizer $authorizer, Precondition $precondition, Validator $validator, \stdClass $model): void
    {
        //
    }
}
