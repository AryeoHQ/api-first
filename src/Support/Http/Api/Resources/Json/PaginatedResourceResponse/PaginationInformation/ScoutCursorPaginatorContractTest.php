<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation;

use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verifies that Scout's CursorPaginator serialization satisfies the meta.paging contract.
 *
 * CursorPaginator::toArray() produces the keys prev_cursor, next_cursor, prev_page_url,
 * next_page_url, and per_page — exactly the keys Paging::from() reads. These tests confirm
 * that contract holds for every page-position case: first page (advancing), last page
 * (terminal), and empty result set.
 */
#[CoversClass(Paging::class)]
final class ScoutCursorPaginatorContractTest extends TestCase
{
    #[Test]
    public function it_maps_an_advancing_cursor_paginator_to_paging(): void
    {
        // First page: no prior cursor, has more pages ahead.
        // CursorPaginator::toArray() for this state produces prev_cursor=null, next_cursor=<encoded>.
        // We simulate the toArray() shape directly since cursor encoding requires model attributes;
        // the keys are authoritative from Laravel's CursorPaginator::toArray() implementation.
        $result = Paging::from([
            'prev_cursor' => null,
            'next_cursor' => 'eyJpZCI6MTAwfQ',
            'prev_page_url' => null,
            'next_page_url' => 'https://api.example.com/companies?cursor=eyJpZCI6MTAwfQ',
            'per_page' => 15,
        ]);

        $this->assertNull($result['before']);
        $this->assertNull($result['before_url']);
        $this->assertSame('eyJpZCI6MTAwfQ', $result['after']);
        $this->assertStringContainsString('paging%5Bcursor%5D=eyJpZCI6MTAwfQ', $result['after_url']);
        $this->assertSame(15, $result['size']);
    }

    #[Test]
    public function it_maps_a_terminal_cursor_paginator_to_paging(): void
    {
        // Last page: cursor provided (has a prev page), items ≤ perPage (no next page).
        // CursorPaginator produces prev_cursor from the passed Cursor, next_cursor=null.
        $cursor = new Cursor(['id' => 'abc-123']);

        $paginator = new CursorPaginator(
            items: [['id' => 'def-456'], ['id' => 'ghi-789']],
            perPage: 15,
            cursor: $cursor,
        );
        $paginator->setPath('https://api.example.com/companies');

        $array = $paginator->toArray();

        $this->assertNotNull($array['prev_cursor'], 'Terminal page must have a prev cursor');
        $this->assertNull($array['next_cursor'], 'Terminal page must not have a next cursor');

        $result = Paging::from($array);

        $this->assertNotNull($result);
        $this->assertNotNull($result['before']);
        $this->assertNull($result['after']);
        $this->assertSame(15, $result['size']);
    }

    #[Test]
    public function it_returns_null_paging_for_an_empty_result_set(): void
    {
        // Empty result: no items, no cursors → Paging::from() must return null.
        $paginator = new CursorPaginator(items: [], perPage: 15);
        $array = $paginator->toArray();

        $this->assertNull($array['prev_cursor']);
        $this->assertNull($array['next_cursor']);

        $result = Paging::from($array);

        $this->assertNull($result);
    }
}
