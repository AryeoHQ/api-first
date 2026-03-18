<?php

declare(strict_types=1);

namespace Support\Http\Api\Providers;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Http\Requests\Contracts\CastableData;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\CastableController;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\PlainController;
use Tests\TestCase;

#[CoversClass(Provider::class)]
final class ProviderTest extends TestCase
{
    #[Test]
    public function it_resolves_castable_data_from_route_with_castable_form_request(): void
    {
        $request = Request::create('/test', 'GET', [
            'filters' => ['is_active' => '1', 'count' => '5'],
        ]);

        $route = new Route('GET', '/test', ['uses' => CastableController::class.'@index']);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->app->instance('request', $request);

        $resolved = app(CastableData::class);

        $this->assertInstanceOf(CastableData::class, $resolved);
    }

    #[Test]
    public function it_resolves_null_when_route_has_no_castable_data_parameter(): void
    {
        $request = Request::create('/test', 'GET');

        $route = new Route('GET', '/test', ['uses' => PlainController::class.'@index']);
        $request->setRouteResolver(fn () => $route);

        $this->app->instance('request', $request);

        $resolved = app(CastableData::class);

        $this->assertNull($resolved);
    }

    #[Test]
    public function it_resolves_null_when_there_is_no_route(): void
    {
        $this->app->instance('request', new Request);

        $resolved = app(CastableData::class);

        $this->assertNull($resolved);
    }
}
