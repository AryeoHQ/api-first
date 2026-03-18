<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PagingInformation;

use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Http\Api\Resources\Json\ExampleResource;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\CastableController;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\PlainController;
use Tests\TestCase;

#[CoversClass(PagingInformation::class)]
final class PagingInformationTest extends TestCase
{
    #[Test]
    public function it_maps_cursors_to_paging_before_and_after(): void
    {
        $closure = (new PagingInformation)->paginationInformation();

        $result = $closure(
            new Request,
            ['prev_cursor' => 'abc', 'next_cursor' => 'def'],
            ['links' => [], 'meta' => []],
        );

        $this->assertSame([
            'paging' => [
                'before' => 'abc',
                'before_url' => null,
                'after' => 'def',
                'after_url' => null,
                'size' => null,
            ],
        ], $result);
    }

    #[Test]
    public function it_returns_null_paging_when_no_cursors_exist(): void
    {
        $closure = (new PagingInformation)->paginationInformation();

        $result = $closure(
            new Request,
            ['data' => []],
            ['links' => [], 'meta' => []],
        );

        $this->assertSame([
            'paging' => null,
        ], $result);
    }

    #[Test]
    public function it_customizes_paginated_response_paging_information(): void
    {
        $paginator = new CursorPaginator(
            items: [['id' => 1]],
            perPage: 1,
        );

        $response = ExampleResource::collection($paginator)
            ->toResponse(new Request);

        $this->assertSame([
            'data' => [
                ['id' => 1],
            ],
            'paging' => null,
        ], $response->getData(assoc: true));
    }

    #[Test]
    public function it_returns_cast_filter_values_from_castable_form_request(): void
    {
        $request = Request::create('/test', 'GET', [
            'filters' => ['is_active' => '1', 'count' => '5'],
        ]);

        $route = new Route('GET', '/test', ['uses' => CastableController::class.'@index']);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->app->instance('request', $request);

        $closure = (new PagingInformation)->paginationInformation();

        $result = $closure(
            $request,
            ['prev_cursor' => 'abc', 'next_cursor' => 'def'],
            [],
        );

        $this->assertSame([
            'paging' => [
                'before' => 'abc',
                'before_url' => null,
                'after' => 'def',
                'after_url' => null,
                'size' => null,
            ],
            'filter' => [
                'is_active' => true,
                'count' => 5,
            ],
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

        $closure = (new PagingInformation)->paginationInformation();

        $result = $closure(
            $request,
            ['prev_cursor' => 'abc', 'next_cursor' => 'def'],
            [],
        );

        $this->assertSame([
            'paging' => [
                'before' => 'abc',
                'before_url' => null,
                'after' => 'def',
                'after_url' => null,
                'size' => null,
            ],
            'filter' => [
                'status' => 'active',
            ],
        ], $result);
    }

    #[Test]
    public function it_omits_filter_key_when_no_filters_present(): void
    {
        $request = Request::create('/test', 'GET');

        $route = new Route('GET', '/test', ['uses' => PlainController::class.'@index']);
        $request->setRouteResolver(fn () => $route);

        $closure = (new PagingInformation)->paginationInformation();

        $result = $closure(
            $request,
            ['prev_cursor' => 'abc', 'next_cursor' => 'def'],
            [],
        );

        $this->assertArrayNotHasKey('filter', $result);
    }

    #[Test]
    public function it_handles_no_route_context_gracefully(): void
    {
        $closure = (new PagingInformation)->paginationInformation();

        $result = $closure(
            new Request,
            ['prev_cursor' => 'abc', 'next_cursor' => 'def'],
            [],
        );

        $this->assertArrayNotHasKey('filter', $result);
    }
}
