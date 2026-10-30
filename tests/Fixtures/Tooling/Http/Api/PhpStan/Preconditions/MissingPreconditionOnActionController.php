<?php

declare(strict_types=1);

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;

final class MissingPreconditionOnActionController
{
    #[Route('api.v1.widgets.actions.archive', 'api/v1/widgets/{widget}/actions/archive', Method::Post)]
    public function __invoke(Authorizer $authorizer, Validator $validator, \stdClass $model): void
    {
        //
    }
}
