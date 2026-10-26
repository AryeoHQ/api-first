<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(Paging::class)]
final class PagingTest extends TestCase
{
    #[Test]
    public function it_returns_paging_data_when_cursors_exist(): void
    {
        $result = Paging::from(new Request, [
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
        $result = Paging::from(new Request, ['data' => []]);

        $this->assertNull($result);
    }

    #[Test]
    public function it_preserves_other_query_parameters_when_rewriting_urls(): void
    {
        $result = Paging::from(new Request, [
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
    public function it_repeats_the_request_query_and_replaces_only_the_cursor(): void
    {
        $request = Request::create('https://example.com', 'GET', [
            'sort' => '-created_at',
            'filters' => ['type' => 'video'],
            'paging' => ['size' => '2', 'cursor' => 'abc'],
        ]);

        $result = Paging::from($request, [
            'prev_cursor' => null,
            'next_cursor' => 'def',
            'prev_page_url' => null,
            'next_page_url' => 'https://example.com?cursor=def',
            'per_page' => 2,
        ]);

        parse_str((string) parse_url($result['after_url'], PHP_URL_QUERY), $query);

        $this->assertSame([
            'sort' => '-created_at',
            'filters' => ['type' => 'video'],
            'paging' => ['size' => '2', 'cursor' => 'def'],
        ], $query);
    }

    #[Test]
    public function it_replaces_a_list_the_paginator_appends_instead_of_merging_it_by_index(): void
    {
        $request = Request::create('https://example.com', 'GET', [
            'filters' => ['status' => ['draft', 'archived']],
            'paging' => ['size' => '2'],
        ]);

        $result = Paging::from($request, [
            'prev_cursor' => null,
            'next_cursor' => 'def',
            'prev_page_url' => null,
            'next_page_url' => 'https://example.com?filters%5Bstatus%5D%5B0%5D=published&cursor=def',
            'per_page' => 2,
        ]);

        parse_str((string) parse_url($result['after_url'], PHP_URL_QUERY), $query);

        $this->assertSame([
            'filters' => ['status' => ['published']],
            'paging' => ['size' => '2', 'cursor' => 'def'],
        ], $query);
    }

    #[Test]
    public function it_returns_null_urls_when_paginator_provides_null_urls(): void
    {
        $result = Paging::from(new Request, [
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
