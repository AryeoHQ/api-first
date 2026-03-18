<?php

namespace Workbench\App\Http\Api\V1\MakeControllerTests\Show;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;
use \Workbench\App\Entities\MakeControllerTests\MakeControllerTest;

final class Controller
{
    #[Route(
        name: 'api.V1.makecontrollertests.show',
        uri: 'api/v1/makecontrollertests/{makecontrollertest}',
        methods: Method::Get,
    )]
    public function __invoke(Authorizer $authorizer, Validator $validator, MakeControllerTest $makecontrollertest)
    {
        //
    }
}
