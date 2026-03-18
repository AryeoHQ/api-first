<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PagingInformation;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\CastableController;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\PlainController;
use Tests\TestCase;

#[CoversClass(Filters::class)]
final class FiltersTest extends TestCase
{
    #[Test]
    public function it_returns_cast_filter_values_from_castable_data(): void
    {
        $request = Request::create('/test', 'GET', [
            'filters' => ['is_active' => '1', 'count' => '5'],
        ]);

        $route = new Route('GET', '/test', ['uses' => CastableController::class.'@index']);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->app->instance('request', $request);

        $result = (new Filters)($request);

        $this->assertSame([
            'is_active' => true,
            'count' => 5,
        ], $result);
    }

    #[Test]
    public function it_returns_raw_filter_values_from_plain_form_request(): void
    {
        $request = Request::create('/test', 'GET', [
            'filters' => ['status' => 'active'],
        ]);

        $route = new Route('GET', '/test', ['uses' => PlainController::class.'@index']);
        $request->setRouteResolver(fn () => $route);

        $this->app->instance('request', $request);

        $result = (new Filters)($request);

        $this->assertSame(['status' => 'active'], $result);
    }

    #[Test]
    public function it_returns_empty_array_when_no_filters_present(): void
    {
        $request = Request::create('/test', 'GET');

        $this->app->instance('request', $request);

        $result = (new Filters)($request);

        $this->assertSame([], $result);
    }
}
