<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Provides;

use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\CastableController;
use Tests\Fixtures\Support\Http\Api\Resources\Json\Post;

trait WithStructuredMetaSortTestCases
{
    #[Test]
    public function it_includes_sort_in_response_when_request_has_sort(): void
    {
        $paginator = new CursorPaginator(
            items: [['id' => 1]],
            perPage: 1,
        );

        $request = Request::create('/test', 'GET', [
            'sort' => '-created_at',
        ]);
        $response = Post::collection($paginator)->toResponse($request);

        $data = $response->getData(assoc: true);

        $this->assertSame('-created_at', $data['meta']['sort']);
    }

    #[Test]
    public function it_returns_null_sort_when_request_has_no_sort(): void
    {
        $paginator = new CursorPaginator(
            items: [['id' => 1]],
            perPage: 1,
        );

        $request = Request::create('/test', 'GET');
        $response = Post::collection($paginator)->toResponse($request);

        $this->assertNull($response->getData(assoc: true)['meta']['sort']);
    }

    #[Test]
    public function it_returns_casted_sort_value_from_castable_form_request(): void
    {
        $paginator = new CursorPaginator(
            items: [['id' => 1]],
            perPage: 1,
        );

        $request = tap(Request::create('/test', 'GET', [
            'sort' => '-created_at',
        ]), fn (Request $request) => $request->setRouteResolver(
            fn () => tap(new Route('GET', '/test', ['uses' => CastableController::class.'@index']), fn (Route $route) => $route->bind($request))
        ));

        $this->app->instance('request', $request);

        $response = Post::collection($paginator)->toResponse($request);

        $data = $response->getData(assoc: true);

        $this->assertSame('-created_at', $data['meta']['sort']);
    }
}
