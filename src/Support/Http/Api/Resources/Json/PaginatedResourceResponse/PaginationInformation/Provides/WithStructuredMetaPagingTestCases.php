<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Provides;

use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse\PagedPost;
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

    #[Test]
    public function it_keeps_the_sort_and_page_size_when_following_paging_urls(): void
    {
        PagedPost::query()->insert(array_map(fn (int $id): array => ['id' => $id], range(1, 5)));

        Route::get('/paged-posts', fn (Request $request) => Post::collection(
            PagedPost::query()
                ->orderBy('id', $request->query('sort') === '-id' ? 'desc' : 'asc')
                ->cursorPaginate($request->has('paging.size') ? $request->integer('paging.size') : null)
        ));

        $first = $this->getJson('/paged-posts?sort=-id&paging[size]=2');
        $second = $this->getJson($first->json('meta.paging.after_url'));
        $back = $this->getJson($second->json('meta.paging.before_url'));

        $this->assertSame([5, 4], $first->json('data.*.id'));
        $this->assertSame([3, 2], $second->json('data.*.id'));
        $this->assertSame(2, $second->json('meta.paging.size'));
        $this->assertSame([5, 4], $back->json('data.*.id'));
    }
}
