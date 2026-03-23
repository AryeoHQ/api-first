<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PagingInformation;

use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Http\Api\Resources\Json\ExampleResource;
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
            'meta' => [
                'paging' => [
                    'before' => 'abc',
                    'before_url' => null,
                    'after' => 'def',
                    'after_url' => null,
                    'size' => null,
                ],
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
            'meta' => [
                'paging' => null,
            ],
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
            'meta' => [
                'paging' => null,
            ],
        ], $response->getData(assoc: true));
    }
}
