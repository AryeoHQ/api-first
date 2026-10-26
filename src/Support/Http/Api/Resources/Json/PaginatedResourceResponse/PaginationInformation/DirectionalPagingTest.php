<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation;

use Illuminate\Http\Request;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Http\Api\Pagination\CursorSignature;
use Tests\TestCase;

#[CoversClass(DirectionalPaging::class)]
final class DirectionalPagingTest extends TestCase
{
    private const string FINGERPRINT = 'list';

    #[Test]
    public function it_returns_paging_data_with_signed_cursors_and_urls_built_from_the_request_query(): void
    {
        $request = $this->listRequest([
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

        $before = CursorSignature::sign('abc', self::FINGERPRINT);
        $after = CursorSignature::sign('def', self::FINGERPRINT);

        $this->assertSame([
            'before' => $before,
            'before_url' => "https://example.com/jobs?sort=-created_at&paging%5Bsize%5D=2&paging%5Bbefore%5D={$before}",
            'after' => $after,
            'after_url' => "https://example.com/jobs?sort=-created_at&paging%5Bsize%5D=2&paging%5Bafter%5D={$after}",
            'size' => 2,
        ], $result);
    }

    #[Test]
    public function it_returns_null_when_no_cursors_exist(): void
    {
        $this->assertNull(DirectionalPaging::from(Request::create('https://example.com/jobs'), ['data' => []]));
    }

    #[Test]
    public function it_refuses_to_emit_a_cursor_without_the_list_fingerprint(): void
    {
        $this->expectException(LogicException::class);

        DirectionalPaging::from(Request::create('https://example.com/jobs'), ['next_cursor' => 'def', 'per_page' => 10]);
    }

    #[Test]
    public function it_returns_a_null_url_when_no_page_exists_in_that_direction(): void
    {
        $result = DirectionalPaging::from($this->listRequest(), [
            'prev_cursor' => null,
            'next_cursor' => 'def',
            'per_page' => 10,
        ]);

        $this->assertSame([null, null], [$result['before'] ?? null, $result['before_url'] ?? null]);
    }

    #[Test]
    public function it_drops_both_cursors_from_the_request_query(): void
    {
        $result = DirectionalPaging::from($this->listRequest(['paging' => ['before' => 'old', 'after' => 'older']]), ['next_cursor' => 'def', 'per_page' => 10]);

        $this->assertSame('https://example.com/jobs?paging%5Bafter%5D='.CursorSignature::sign('def', self::FINGERPRINT), $result['after_url'] ?? null);
    }

    #[Test]
    public function it_replaces_a_paging_parameter_that_is_not_a_list(): void
    {
        $result = DirectionalPaging::from($this->listRequest(['paging' => 'x']), ['next_cursor' => 'def', 'per_page' => 10]);

        $this->assertSame('https://example.com/jobs?paging%5Bafter%5D='.CursorSignature::sign('def', self::FINGERPRINT), $result['after_url'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function listRequest(array $query = []): Request
    {
        $request = Request::create('https://example.com/jobs', 'GET', $query);

        $request->attributes->set(CursorSignature::class, self::FINGERPRINT);

        return $request;
    }
}
