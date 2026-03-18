<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PagingInformation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(Paging::class)]
final class PagingTest extends TestCase
{
    #[Test]
    public function it_returns_paging_data_when_cursors_exist(): void
    {
        $result = (new Paging)([
            'prev_cursor' => 'abc',
            'next_cursor' => 'def',
            'prev_page_url' => 'https://example.com?cursor=abc',
            'next_page_url' => 'https://example.com?cursor=def',
            'per_page' => 10,
        ]);

        $this->assertSame([
            'before' => 'abc',
            'before_url' => 'https://example.com?cursor=abc',
            'after' => 'def',
            'after_url' => 'https://example.com?cursor=def',
            'size' => 10,
        ], $result);
    }

    #[Test]
    public function it_returns_null_when_no_cursors_exist(): void
    {
        $result = (new Paging)(['data' => []]);

        $this->assertNull($result);
    }
}
