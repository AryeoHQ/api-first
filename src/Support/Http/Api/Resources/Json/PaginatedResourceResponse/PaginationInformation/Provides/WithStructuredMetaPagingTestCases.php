<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Provides;

use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Http\Api\Resources\Json\Post;

trait WithStructuredMetaPagingTestCases
{
    #[Test]
    public function it_maps_cursors_to_paging_before_and_after(): void
    {
        $result = Post::make((object) ['id' => 1])->paginationInformation(
            new Request,
            ['prev_cursor' => 'abc', 'next_cursor' => 'def'],
            ['links' => [], 'meta' => []],
        );

        $this->assertSame([
            'meta' => [
                'filters' => null,
                'paging' => [
                    'before' => 'abc',
                    'before_url' => null,
                    'after' => 'def',
                    'after_url' => null,
                    'size' => null,
                ],
                'sort' => null,
            ],
        ], $result);
    }

    #[Test]
    public function it_returns_null_paging_when_no_cursors_exist(): void
    {
        $result = Post::make((object) ['id' => 1])->paginationInformation(
            new Request,
            ['data' => []],
            ['links' => [], 'meta' => []],
        );

        $this->assertSame([
            'meta' => [
                'filters' => null,
                'paging' => null,
                'sort' => null,
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

        $response = Post::collection($paginator)->toResponse(new Request);

        $this->assertSame([
            'data' => [
                [
                    'id' => 1,
                    'resource_type' => 'post',
                ],
            ],
            'meta' => [
                'filters' => null,
                'paging' => null,
                'sort' => null,
            ],
        ], $response->getData(assoc: true));
    }
}
