<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;

final class UnguardedPreconditionsController
{
    #[Route(
        name: 'api.v1.widgets.update',
        uri: 'api/v1/widgets/{widget}',
        methods: Method::Patch,
    )]
    public function __invoke(
        Authorizer $authorizer,
        Precondition $precondition,
        Precondition $other,
        Validator $validator,
        \stdClass $model,
    ): void {
        $model->title = 'Final';
    }
}
