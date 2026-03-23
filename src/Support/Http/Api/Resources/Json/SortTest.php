<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\CastableController;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\PlainController;
use Tests\TestCase;

#[CoversClass(Sort::class)]
final class SortTest extends TestCase
{
    #[Test]
    public function it_returns_sort_value_from_castable_data(): void
    {
        $request = Request::create('/test', 'GET', [
            'sort' => '-created_at',
        ]);

        $route = new Route('GET', '/test', ['uses' => CastableController::class.'@index']);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->app->instance('request', $request);

        $result = Sort::from($request);

        $this->assertSame('-created_at', $result);
    }

    #[Test]
    public function it_returns_raw_sort_from_query_when_no_castable_data(): void
    {
        $request = Request::create('/test', 'GET', [
            'sort' => '-updated_at',
        ]);

        $route = new Route('GET', '/test', ['uses' => PlainController::class.'@index']);
        $request->setRouteResolver(fn () => $route);

        $this->app->instance('request', $request);

        $result = Sort::from($request);

        $this->assertSame('-updated_at', $result);
    }

    #[Test]
    public function it_returns_null_when_no_sort_present(): void
    {
        $request = Request::create('/test', 'GET');

        $this->app->instance('request', $request);

        $result = Sort::from($request);

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_when_sort_is_not_a_string(): void
    {
        $request = Request::create('/test', 'GET', [
            'sort' => ['created_at', 'updated_at'],
        ]);

        $route = new Route('GET', '/test', ['uses' => PlainController::class.'@index']);
        $request->setRouteResolver(fn () => $route);

        $this->app->instance('request', $request);

        $result = Sort::from($request);

        $this->assertNull($result);
    }
}
