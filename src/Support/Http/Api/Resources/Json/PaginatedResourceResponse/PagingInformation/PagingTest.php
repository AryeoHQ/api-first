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
            'before_url' => 'https://example.com?paging%5Bcursor%5D=abc',
            'after' => 'def',
            'after_url' => 'https://example.com?paging%5Bcursor%5D=def',
            'size' => 10,
        ], $result);
    }

    #[Test]
    public function it_returns_null_when_no_cursors_exist(): void
    {
        $result = (new Paging)(['data' => []]);

        $this->assertNull($result);
    }

    #[Test]
    public function it_preserves_other_query_parameters_when_rewriting_urls(): void
    {
        $result = (new Paging)([
            'prev_cursor' => 'abc',
            'next_cursor' => 'def',
            'prev_page_url' => 'https://example.com?sort=-created_at&cursor=abc',
            'next_page_url' => 'https://example.com?sort=-created_at&cursor=def',
            'per_page' => 25,
        ]);

        $this->assertStringContainsString('sort=-created_at', $result['before_url']);
        $this->assertStringContainsString('paging%5Bcursor%5D=abc', $result['before_url']);
        $this->assertStringNotContainsString('&cursor=', $result['before_url']);
    }

    #[Test]
    public function it_returns_null_urls_when_paginator_provides_null_urls(): void
    {
        $result = (new Paging)([
            'prev_cursor' => null,
            'next_cursor' => 'def',
            'prev_page_url' => null,
            'next_page_url' => 'https://example.com?cursor=def',
            'per_page' => 10,
        ]);

        $this->assertNull($result['before_url']);
        $this->assertStringContainsString('paging%5Bcursor%5D=def', $result['after_url']);
    }
}
