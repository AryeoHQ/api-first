<?php

declare(strict_types=1);

namespace Support\Http\Api\Pagination;

use Illuminate\Http\Request;
use Illuminate\Pagination\Cursor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;
use Tests\TestCase;

#[CoversClass(CursorPaginator::class)]
final class CursorPaginatorTest extends TestCase
{
    #[Test]
    public function it_encodes_date_parameters_with_millisecond_precision(): void
    {
        $this->createModelsAt('2026-10-25T21:00:00.100+00:00', '2026-10-25T21:00:00.400+00:00');

        $this->app->instance('request', Request::create('/test', 'GET'));

        $page = Rfc3339ExtendedTestModel::query()->orderBy('created_at')->cursorPaginate(1);

        $this->assertSame(
            '2026-10-25T21:00:00.100+00:00',
            $page->nextCursor()?->parameter('created_at'),
        );
    }

    #[Test]
    public function it_pages_forward_through_rows_within_one_second_exactly_once(): void
    {
        $ids = $this->createModelsAt(
            '2026-10-25T21:00:00.100+00:00',
            '2026-10-25T21:00:00.400+00:00',
            '2026-10-25T21:00:00.700+00:00',
        );

        $this->assertSame($ids, $this->pageForward(maxPages: count($ids) + 1));
    }

    #[Test]
    public function it_resolves_a_seconds_precision_cursor(): void
    {
        $ids = $this->createModelsAt(
            '2026-10-25T21:00:00.100+00:00',
            '2026-10-25T21:00:00.400+00:00',
        );

        $this->app->instance('request', Request::create('/test', 'GET', [
            'paging' => ['cursor' => (new Cursor(['created_at' => '2026-10-25 21:00:00']))->encode()],
        ]));

        $page = Rfc3339ExtendedTestModel::query()->orderBy('created_at')->cursorPaginate(1);

        $this->assertSame([$ids[0]], $page->pluck('id')->all());
    }

    /**
     * @return list<string>
     */
    private function createModelsAt(string ...$timestamps): array
    {
        return array_map(
            fn (string $timestamp): string => Rfc3339ExtendedTestModel::factory()->create(['created_at' => $timestamp])->getKey(),
            array_values($timestamps),
        );
    }

    /**
     * @return list<string>
     */
    private function pageForward(int $maxPages): array
    {
        $ids = [];
        $cursor = null;

        foreach (range(1, $maxPages) as $_) {
            $this->app->instance('request', Request::create('/test', 'GET', $cursor === null ? [] : [
                'paging' => ['cursor' => $cursor],
            ]));

            $page = Rfc3339ExtendedTestModel::query()->orderBy('created_at')->cursorPaginate(1);

            $ids = [...$ids, ...$page->pluck('id')->all()];
            $cursor = $page->nextCursor()?->encode();

            if ($cursor === null) {
                break;
            }
        }

        return $ids;
    }
}
