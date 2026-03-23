<?php

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\TwoCastableData;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;

final class Controller
{
    #[Route(
        name: 'test.index',
        uri: '/test',
        methods: Method::Get,
    )]
    public function __invoke(RequestA $requestA, RequestB $requestB)
    {
        //
    }
}
