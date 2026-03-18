<?php

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;

class ControllerNotFinal
{
    #[Route(
        name: 'test.index',
        uri: '/test',
        methods: Method::Get,
    )]
    public function __invoke()
    {
        //
    }
}
