<?php

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\NotFinal;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;

class Controller
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
