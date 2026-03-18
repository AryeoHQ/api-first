<?php

namespace Workbench\App\Http\Api\V1\MakeControllerTests\Index;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;

final class Controller
{
    #[Route(
        name: 'api.V1.makecontrollertests.index',
        uri: 'api/v1/makecontrollertests',
        methods: Method::Get,
    )]
    public function __invoke(Authorizer $authorizer, Validator $validator)
    {
        //
    }
}
