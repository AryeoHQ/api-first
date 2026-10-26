<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(DirectionalPaging::class)]
final class DirectionalPagingTest extends TestCase
{
    #[Test]
    public function it_returns_paging_data_with_urls_built_from_the_request_query(): void
    {
        $request = Request::create('https://example.com/jobs', 'GET', [
            'sort' => '-created_at',
            'paging' => ['size' => '2', 'after' => 'old'],
        ]);

        $result = DirectionalPaging::from($request, [
            'prev_cursor' => 'abc',
            'next_cursor' => 'def',
            'prev_page_url' => 'https://example.com/jobs?paging.after=abc',
            'next_page_url' => 'https://example.com/jobs?paging.after=def',
            'per_page' => 2,
        ]);

        $this->assertSame([
            'before' => 'abc',
            'before_url' => 'https://example.com/jobs?sort=-created_at&paging%5Bsize%5D=2&paging%5Bbefore%5D=abc',
            'after' => 'def',
            'after_url' => 'https://example.com/jobs?sort=-created_at&paging%5Bsize%5D=2&paging%5Bafter%5D=def',
            'size' => 2,
        ], $result);
    }

    #[Test]
    public function it_returns_null_when_no_cursors_exist(): void
    {
        $this->assertNull(DirectionalPaging::from(Request::create('https://example.com/jobs'), ['data' => []]));
    }

    #[Test]
    public function it_returns_a_null_url_when_no_page_exists_in_that_direction(): void
    {
        $result = DirectionalPaging::from(Request::create('https://example.com/jobs'), [
            'prev_cursor' => null,
            'next_cursor' => 'def',
            'per_page' => 10,
        ]);

        $this->assertSame([null, null], [$result['before'] ?? null, $result['before_url'] ?? null]);
    }

    #[Test]
    public function it_drops_both_cursors_from_the_request_query(): void
    {
        $request = Request::create('https://example.com/jobs', 'GET', ['paging' => ['before' => 'old', 'after' => 'older']]);

        $result = DirectionalPaging::from($request, ['next_cursor' => 'def', 'per_page' => 10]);

        $this->assertSame('https://example.com/jobs?paging%5Bafter%5D=def', $result['after_url'] ?? null);
    }

    #[Test]
    public function it_replaces_a_paging_parameter_that_is_not_a_list(): void
    {
        $request = Request::create('https://example.com/jobs', 'GET', ['paging' => 'x']);

        $result = DirectionalPaging::from($request, ['next_cursor' => 'def', 'per_page' => 10]);

        $this->assertSame('https://example.com/jobs?paging%5Bafter%5D=def', $result['after_url'] ?? null);
    }
}
