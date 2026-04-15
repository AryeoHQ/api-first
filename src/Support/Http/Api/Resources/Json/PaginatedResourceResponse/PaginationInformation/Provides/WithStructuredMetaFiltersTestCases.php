<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Provides;

use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\CastableController;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\PlainController;
use Tests\Fixtures\Support\Http\Api\Resources\Json\Post;

trait WithStructuredMetaFiltersTestCases
{
    #[Test]
    public function it_includes_filters_when_request_has_filters(): void
    {
        $paginator = new CursorPaginator(
            items: [['id' => 1]],
            perPage: 1,
        );

        $request = Request::create('/test', 'GET', ['filters' => ['status' => 'active']]);
        $response = Post::collection($paginator)->toResponse($request);

        $this->assertSame(['status' => 'active'], $response->getData(assoc: true)['meta']['filters']);
    }

    #[Test]
    public function it_returns_null_filters_when_request_has_no_filters(): void
    {
        $paginator = new CursorPaginator(
            items: [['id' => 1]],
            perPage: 1,
        );

        $request = Request::create('/test', 'GET');
        $response = Post::collection($paginator)->toResponse($request);

        $this->assertNull($response->getData(assoc: true)['meta']['filters']);
    }

    #[Test]
    public function it_returns_casted_filters_values_from_castable_form_request(): void
    {
        $paginator = new CursorPaginator(
            items: [['id' => 1]],
            perPage: 1,
        );

        $request = tap(Request::create('/test', 'GET', [
            'filters' => ['is_active' => '1', 'count' => '5'],
        ]), fn (Request $request) => $request->setRouteResolver(
            fn () => tap(new Route('GET', '/test', ['uses' => CastableController::class.'@index']), fn (Route $route) => $route->bind($request))
        ));

        $this->app->instance('request', $request);

        $response = Post::collection($paginator)->toResponse($request);

        $data = $response->getData(assoc: true);

        $this->assertSame([
            'is_active' => true,
            'count' => 5,
        ], $data['meta']['filters']);
    }

    #[Test]
    public function it_returns_raw_filters_values_from_plain_form_request(): void
    {
        $paginator = new CursorPaginator(
            items: [['id' => 1]],
            perPage: 1,
        );

        $request = tap(Request::create('/test', 'GET', [
            'filters' => ['is_active' => '1', 'count' => '5'],
        ]), fn (Request $request) => $request->setRouteResolver(
            fn () => tap(new Route('GET', '/test', ['uses' => PlainController::class.'@index']), fn (Route $route) => $route->bind($request))
        ));

        $this->app->instance('request', $request);

        $response = Post::collection($paginator)->toResponse($request);

        $data = $response->getData(assoc: true);

        $this->assertSame(['is_active' => '1', 'count' => '5'], $data['meta']['filters']);
    }
}
