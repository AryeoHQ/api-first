<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Provides;

use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Support\Routing\RouteRegistrar;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;
use Tests\Fixtures\Support\Http\Api\Request\ListQuery\Controller;
use Tests\Fixtures\Support\Http\Api\Request\ListQuery\Resource;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\CastableController;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\PlainController;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\StringableController;
use Tests\TestCase;

#[CoversTrait(WithDirectionalMeta::class)]
final class WithDirectionalMetaTest extends TestCase
{
    /**
     * @param  \Illuminate\Routing\Router  $router
     */
    protected function defineRoutes($router): void
    {
        app(RouteRegistrar::class)->registerFile((string) (new ReflectionClass(Controller::class))->getFileName());
    }

    #[Test]
    public function it_links_the_next_page_with_a_url_that_repeats_the_query(): void
    {
        $this->createModelsAt('2026-10-25T21:00:00.100+00:00', '2026-10-25T21:00:00.400+00:00');

        $response = $this->getJson('list-query/rfc3339?'.Arr::query([
            'paging' => ['size' => 1],
            'sort' => 'created_at',
            'filters' => ['status' => 'active'],
        ]))->assertOk();

        $after = $response->json('meta.paging.after');

        $this->assertSame([
            'filters' => ['status' => 'active'],
            'paging' => [
                'before' => null,
                'before_url' => null,
                'after' => $after,
                'after_url' => "http://localhost/list-query/rfc3339?paging%5Bsize%5D=1&paging%5Bafter%5D={$after}&sort=created_at&filters%5Bstatus%5D=active",
                'size' => 1,
            ],
            'sort' => 'created_at',
        ], $response->json('meta'));
    }

    #[Test]
    public function it_accepts_its_own_url_that_repeats_the_sort_and_filters(): void
    {
        $ids = $this->createModelsAt('2026-10-25T21:00:00.100+00:00', '2026-10-25T21:00:00.400+00:00');

        $after = $this->getJson('list-query/rfc3339?'.Arr::query([
            'paging' => ['size' => 1],
            'sort' => 'created_at',
            'filters' => ['status' => 'active', 'created' => ['to' => 'b', 'from' => 'a']],
        ]))->assertOk()->json('meta.paging.after_url');

        $this->getJson($after)
            ->assertOk()
            ->assertJsonPath('data.*.id', [$ids[1]]);
    }

    #[Test]
    public function it_replaces_the_requested_cursor_in_each_url(): void
    {
        $this->createModelsAt('2026-10-25T21:00:00.100+00:00', '2026-10-25T21:00:00.400+00:00', '2026-10-25T21:00:00.700+00:00');

        $cursor = $this->getJson('list-query/rfc3339?'.Arr::query(['paging' => ['size' => 1]]))->assertOk()->json('meta.paging.after');

        $response = $this->getJson('list-query/rfc3339?'.Arr::query(['paging' => ['size' => 1, 'after' => $cursor]]))->assertOk();

        $before = $response->json('meta.paging.before');
        $after = $response->json('meta.paging.after');

        $this->assertSame([
            'before' => $before,
            'before_url' => "http://localhost/list-query/rfc3339?paging%5Bsize%5D=1&paging%5Bbefore%5D={$before}",
            'after' => $after,
            'after_url' => "http://localhost/list-query/rfc3339?paging%5Bsize%5D=1&paging%5Bafter%5D={$after}",
            'size' => 1,
        ], $response->json('meta.paging'));
    }

    #[Test]
    public function it_returns_null_paging_when_one_page_holds_the_list(): void
    {
        $this->createModelsAt('2026-10-25T21:00:00.100+00:00');

        $this->getJson('list-query/rfc3339')
            ->assertOk()
            ->assertExactJson([
                'data' => [['id' => Rfc3339ExtendedTestModel::query()->value('id'), 'resource_type' => 'list_query_item']],
                'meta' => ['filters' => null, 'paging' => null, 'sort' => null],
            ]);
    }

    #[Test]
    public function it_includes_filters_when_request_has_filters(): void
    {
        $request = Request::create('/test', 'GET', ['filters' => ['status' => 'active']]);

        $this->assertSame(['status' => 'active'], $this->metaFor($request)['filters']);
    }

    #[Test]
    public function it_returns_null_filters_when_request_has_no_filters(): void
    {
        $this->assertNull($this->metaFor(Request::create('/test', 'GET'))['filters']);
    }

    #[Test]
    public function it_returns_casted_filters_values_from_castable_form_request(): void
    {
        $request = $this->routedRequest(CastableController::class, ['filters' => ['is_active' => '1', 'count' => '5']]);

        $this->assertSame([
            'is_active' => true,
            'count' => 5,
        ], $this->metaFor($request)['filters']);
    }

    #[Test]
    public function it_returns_raw_filters_values_from_plain_form_request(): void
    {
        $request = $this->routedRequest(PlainController::class, ['filters' => ['is_active' => '1', 'count' => '5']]);

        $this->assertSame(['is_active' => '1', 'count' => '5'], $this->metaFor($request)['filters']);
    }

    #[Test]
    public function it_includes_sort_in_response_when_request_has_sort(): void
    {
        $request = Request::create('/test', 'GET', ['sort' => '-created_at']);

        $this->assertSame('-created_at', $this->metaFor($request)['sort']);
    }

    #[Test]
    public function it_returns_null_sort_when_request_has_no_sort(): void
    {
        $this->assertNull($this->metaFor(Request::create('/test', 'GET'))['sort']);
    }

    #[Test]
    public function it_returns_casted_sort_value_from_castable_form_request(): void
    {
        $request = $this->routedRequest(CastableController::class, ['sort' => '-created_at']);

        $this->assertSame('-created_at', $this->metaFor($request)['sort']);
    }

    #[Test]
    public function it_returns_stringable_sort_value_from_castable_data(): void
    {
        $request = $this->routedRequest(StringableController::class, ['sort' => '-created_at']);

        $this->assertSame('-created_at', $this->metaFor($request)['sort']);
    }

    /**
     * @param  class-string  $controller
     * @param  array<string, mixed>  $query
     */
    private function routedRequest(string $controller, array $query): Request
    {
        $request = tap(Request::create('/test', 'GET', $query), fn (Request $request) => $request->setRouteResolver(
            fn () => tap(new Route('GET', '/test', ['uses' => $controller.'@index']), fn (Route $route) => $route->bind($request))
        ));

        $this->app->instance('request', $request);

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    private function metaFor(Request $request): array
    {
        $paginator = new CursorPaginator(
            items: [Rfc3339ExtendedTestModel::factory()->make()],
            perPage: 1,
        );

        return Resource::collection($paginator)->toResponse($request)->getData(assoc: true)['meta'];
    }

    /**
     * @return list<string>
     */
    private function createModelsAt(string ...$timestamps): array
    {
        return array_map(
            fn (string $timestamp): string => Rfc3339ExtendedTestModel::query()->forceCreate(['created_at' => $timestamp])->getKey(),
            array_values($timestamps),
        );
    }
}
