<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\CastableController;
use Tests\TestCase;

use function PHPUnit\Framework\assertInstanceOf;

#[CoversClass(AppendSort::class)]
final class AppendSortTest extends TestCase
{
    #[Test]
    public function it_appends_sort_to_response_when_request_has_sort(): void
    {
        $request = Request::create('/test', 'GET', [
            'sort' => '-created_at',
        ]);

        $middleware = new AppendSort;

        $response = $middleware->handle($request, fn () => new JsonResponse([
            'data' => [['id' => 1]],
        ]));

        assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(assoc: true);

        $this->assertSame('-created_at', $data['meta']['sort']);
    }

    #[Test]
    public function it_does_not_append_sort_when_request_has_no_sort(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new AppendSort;

        $response = $middleware->handle($request, fn () => new JsonResponse([
            'data' => ['id' => 1],
        ]));

        assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(assoc: true);

        $this->assertArrayNotHasKey('meta', $data);
    }

    #[Test]
    public function it_returns_cast_sort_value_from_castable_form_request(): void
    {
        $request = Request::create('/test', 'GET', [
            'sort' => '-created_at',
        ]);

        $route = new Route('GET', '/test', ['uses' => CastableController::class.'@index']);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->app->instance('request', $request);

        $middleware = new AppendSort;

        $response = $middleware->handle($request, fn () => new JsonResponse([
            'data' => [['id' => 1]],
        ]));

        assertInstanceOf(JsonResponse::class, $response);
        $data = $response->getData(assoc: true);

        $this->assertSame('-created_at', $data['meta']['sort']);
    }
}
