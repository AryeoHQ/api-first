<?php

declare(strict_types=1);

namespace Support\Http\Api\Providers;

use Illuminate\Http\Request;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
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
        tap(
            Request::create('/test', 'GET', ['filters' => ['is_active' => '1', 'count' => '5']]),
            function (Request $request) {
                $request->setRouteResolver(fn () => tap(
                    new Route('GET', '/test', ['uses' => CastableController::class.'@index']),
                    fn (Route $route) => $route->bind($request),
                ));

                $this->app->instance('request', $request);
            });

        $this->assertInstanceOf(CastableData::class, app(CastableData::class));
    }

    #[Test]
    public function it_resolves_null_when_route_has_no_castable_data_parameter(): void
    {
        tap(Request::create('/test', 'GET'), function (Request $request) {
            $request->setRouteResolver(
                fn () => new Route('GET', '/test', ['uses' => PlainController::class.'@index']),
            );

            $this->app->instance('request', $request);
        });

        $this->assertNull(app(CastableData::class));
    }

    #[Test]
    public function it_resolves_null_when_there_is_no_route(): void
    {
        $this->app->instance('request', new Request);

        $this->assertNull(app(CastableData::class));
    }

    #[Test]
    public function it_resolves_cursor_from_paging_cursor_query_parameter(): void
    {
        $this->app->instance('request', Request::create('/test', 'GET', [
            'paging' => ['cursor' => 'eyJpZCI6MTAsIl9wb2ludHNUb05leHRJdGVtcyI6dHJ1ZX0'],
        ]));

        $this->assertInstanceOf(Cursor::class, CursorPaginator::resolveCurrentCursor());
    }

    #[Test]
    public function it_resolves_null_cursor_when_paging_cursor_is_absent(): void
    {
        $this->app->instance('request', Request::create('/test', 'GET'));

        $cursor = CursorPaginator::resolveCurrentCursor();

        $this->assertNull($cursor);
    }
}
