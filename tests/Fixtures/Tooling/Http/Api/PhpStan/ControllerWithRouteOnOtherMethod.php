<?php

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;

final class ControllerWithRouteOnOtherMethod
{
    #[Route(
        name: 'test.index',
        uri: '/test',
        methods: Method::Get,
    )]
    public function __invoke(): void {}

    #[Route(
        name: 'test.other',
        uri: '/test/other',
        methods: Method::Get,
    )]
    public function index(): void {}
}
