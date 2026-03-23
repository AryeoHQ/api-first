<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\CastableController;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\PlainController;
use Tests\TestCase;

#[CoversClass(AppendFilters::class)]
final class AppendFiltersTest extends TestCase
{
    #[Test]
    public function it_appends_filters_to_response_when_request_has_filters(): void
    {
        $request = tap(
            Request::create('/test', 'GET', ['filters' => ['status' => 'active']]),
            fn (Request $request) => $request->setRouteResolver(
                fn () => new Route('GET', '/test', ['uses' => PlainController::class.'@index'])
            )
        );

        $response = new AppendFilters()->handle($request, fn () => new JsonResponse([
            'data' => ['id' => 1],
        ]));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(assoc: true);

        $this->assertSame(['status' => 'active'], $data['meta']['filters']);
    }

    #[Test]
    public function it_does_not_append_filters_when_request_has_no_filters(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new AppendFilters;

        $response = $middleware->handle($request, fn () => new JsonResponse([
            'data' => ['id' => 1],
        ]));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(assoc: true);

        $this->assertArrayNotHasKey('meta', $data);
    }

    #[Test]
    public function it_returns_cast_filter_values_from_castable_form_request(): void
    {
        $request = tap(Request::create('/test', 'GET', [
            'filters' => ['is_active' => '1', 'count' => '5'],
        ]), fn (Request $request) => $request->setRouteResolver(
            fn () => tap(new Route('GET', '/test', ['uses' => CastableController::class.'@index']), fn (Route $route) => $route->bind($request))
        ));

        $this->app->instance('request', $request);

        $middleware = new AppendFilters;

        $response = $middleware->handle($request, fn () => new JsonResponse([
            'data' => [['id' => 1]],
        ]));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(assoc: true);

        $this->assertSame([
            'is_active' => true,
            'count' => 5,
        ], $data['meta']['filters']);
    }

    #[Test]
    public function it_returns_raw_filter_values_from_plain_form_request(): void
    {
        $request = tap(
            Request::create('/test', 'GET', ['filters' => ['status' => 'active']]),
            fn (Request $request) => $request->setRouteResolver(
                fn () => new Route('GET', '/test', ['uses' => PlainController::class.'@index'])
            )
        );

        $middleware = new AppendFilters;

        $response = $middleware->handle($request, fn () => new JsonResponse([
            'data' => [['id' => 1]],
        ]));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(assoc: true);

        $this->assertSame(['status' => 'active'], $data['meta']['filters']);
    }
}
