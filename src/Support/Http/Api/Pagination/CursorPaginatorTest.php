<?php

declare(strict_types=1);

namespace Support\Http\Api\Pagination;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;
use Tests\Fixtures\Support\Http\Api\Pagination\PagingTitledTestModel;
use Tests\TestCase;

#[CoversClass(CursorPaginator::class)]
final class CursorPaginatorTest extends TestCase
{
    #[Test]
    public function it_encodes_date_parameters_with_millisecond_precision(): void
    {
        $this->createModelsAt(Rfc3339ExtendedTestModel::class, '2026-10-25T21:00:00.100+00:00', '2026-10-25T21:00:00.400+00:00');

        $this->assertSame('2026-10-25T21:00:00.100+00:00', $this->firstNextCursorParameter());
    }

    #[Test]
    public function it_encodes_a_whole_millisecond_date_parameter_with_millisecond_precision(): void
    {
        $this->insertRowsAt('2026-10-25T21:00:00.123000+00:00', '2026-10-25T21:00:00.400000+00:00');

        $this->assertSame('2026-10-25T21:00:00.123+00:00', $this->firstNextCursorParameter());
    }

    #[Test]
    public function it_encodes_a_whole_second_date_parameter_with_millisecond_precision(): void
    {
        $this->insertRowsAt('2026-10-25 21:00:00', '2026-10-25 22:00:00');

        $this->assertSame('2026-10-25T21:00:00.000+00:00', $this->firstNextCursorParameter());
    }

    #[Test]
    public function it_truncates_a_sub_millisecond_date_parameter_to_millisecond_precision(): void
    {
        $this->insertRowsAt('2026-10-25T21:00:00.123789+00:00', '2026-10-25T21:00:00.400000+00:00');

        $this->assertSame('2026-10-25T21:00:00.123+00:00', $this->firstNextCursorParameter());
    }

    #[Test]
    public function it_pages_forward_through_rows_within_one_second_exactly_once(): void
    {
        $ids = $this->createModelsAt(
            Rfc3339ExtendedTestModel::class,
            '2026-10-25T21:00:00.100+00:00',
            '2026-10-25T21:00:00.400+00:00',
            '2026-10-25T21:00:00.700+00:00',
        );

        $this->assertSame($ids, $this->pageForward(Rfc3339ExtendedTestModel::class, maxPages: count($ids) + 1));
    }

    #[Test]
    public function it_resolves_a_seconds_precision_cursor(): void
    {
        $ids = $this->createModelsAt(
            Rfc3339ExtendedTestModel::class,
            '2026-10-24T21:00:00.000+00:00',
            '2026-10-26T21:00:00.000+00:00',
        );

        $this->app->instance('request', Request::create('/test', 'GET', [
            'paging' => ['after' => CursorSignature::sign((new Cursor(['created_at' => '2026-10-25 21:00:00']))->encode(), CursorSignature::fingerprint(Request::create('/test')))],
        ]));

        $page = Rfc3339ExtendedTestModel::query()->orderBy('created_at')->cursorPaginate(count($ids));

        $this->assertSame([$ids[1]], $page->pluck('id')->all());
    }

    #[Test]
    public function it_encodes_the_page_url_cursor_as_its_next_cursor(): void
    {
        Schema::create('paging_titled_test_models', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->timestampsTz();
        });

        foreach (['AC/DC é', 'Zappa'] as $title) {
            PagingTitledTestModel::query()->forceCreate(['title' => $title]);
        }

        $this->app->instance('request', Request::create('/test', 'GET'));

        $page = PagingTitledTestModel::query()->orderBy('title')->orderBy('id')->cursorPaginate(1)->toArray();

        parse_str((string) parse_url((string) $page['next_page_url'], PHP_URL_QUERY), $query);

        $this->assertSame($page['next_cursor'], $query['cursor'] ?? null);
    }

    /**
     * @param  class-string<Model>  $model
     * @return list<string>
     */
    private function createModelsAt(string $model, string ...$timestamps): array
    {
        return array_map(
            fn (string $timestamp): string => $model::query()->forceCreate(['created_at' => $timestamp])->getKey(),
            array_values($timestamps),
        );
    }

    private function insertRowsAt(string ...$timestamps): void
    {
        DB::table((new Rfc3339ExtendedTestModel)->getTable())->insert(array_map(
            fn (string $timestamp): array => ['id' => (string) Str::uuid(), 'created_at' => $timestamp],
            array_values($timestamps),
        ));
    }

    private function firstNextCursorParameter(): mixed
    {
        $this->app->instance('request', Request::create('/test', 'GET'));

        return Rfc3339ExtendedTestModel::query()->orderBy('created_at')->cursorPaginate(1)->nextCursor()?->parameter('created_at');
    }

    /**
     * @param  class-string<Model>  $model
     * @return list<string>
     */
    private function pageForward(string $model, int $maxPages): array
    {
        $ids = [];
        $cursor = null;

        foreach (range(1, $maxPages) as $_) {
            $this->app->instance('request', Request::create('/test', 'GET'));

            $page = $model::query()->orderBy('created_at')->cursorPaginate(1, cursor: $cursor);

            $ids = [...$ids, ...$page->pluck('id')->all()];
            $cursor = $page->nextCursor()?->encode();

            if ($cursor === null) {
                break;
            }
        }

        return $ids;
    }
}
