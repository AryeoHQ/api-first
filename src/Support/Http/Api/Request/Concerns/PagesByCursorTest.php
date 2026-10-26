<?php

declare(strict_types=1);

namespace Support\Http\Api\Request\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Support\Routing\RouteRegistrar;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;
use Tests\Fixtures\Support\Http\Api\Pagination\MicrosecondsDateFormatTestModel;
use Tests\Fixtures\Support\Http\Api\Request\ListQuery\Controller;
use Tests\TestCase;

#[CoversTrait(PagesByCursor::class)]
final class PagesByCursorTest extends TestCase
{
    private const string ID = '0199f7a4-0000-7000-8000-000000000000';

    /**
     * @return array<string, array{string}>
     */
    public static function cursorsThisListDidNotIssue(): array
    {
        return [
            'an undecodable value' => ['not-a-cursor'],
            'a cursor for another order' => [(new Cursor(['id' => self::ID]))->encode()],
            'a seconds date that does not exist' => [(new Cursor(['created_at' => '2026-02-31 00:00:00', 'id' => self::ID]))->encode()],
            'a milliseconds date that does not exist' => [(new Cursor(['created_at' => '2026-02-31T00:00:00.000+00:00', 'id' => self::ID]))->encode()],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function sizesOutOfRange(): array
    {
        return [
            'zero' => ['0'],
            'above the maximum' => ['101'],
            'not an integer' => ['ten'],
        ];
    }

    /**
     * @param  \Illuminate\Routing\Router  $router
     */
    protected function defineRoutes($router): void
    {
        app(RouteRegistrar::class)->registerFile((string) (new ReflectionClass(Controller::class))->getFileName());
    }

    #[Test]
    public function it_walks_rows_within_one_second_forward_and_back_once_each(): void
    {
        $ids = $this->createModelsAt(
            Rfc3339ExtendedTestModel::class,
            '2026-10-25T21:00:00.100+00:00',
            '2026-10-25T21:00:00.400+00:00',
            '2026-10-25T21:00:00.700+00:00',
        );

        $forward = $this->follow('list-query/rfc3339?paging[size]=1', 'after_url', maxPages: count($ids) + 1);
        $backward = $this->follow($forward['last']['before_url'], 'before_url', maxPages: count($ids) + 1);

        $this->assertSame([$ids, [$ids[1], $ids[0]]], [$forward['ids'], $backward['ids']]);
    }

    #[Test]
    public function it_walks_a_model_by_its_own_date_format(): void
    {
        $ids = $this->createModelsAt(MicrosecondsDateFormatTestModel::class, '2026-10-25 21:00:00.100000', '2026-10-25 21:00:00.400000');

        $forward = $this->follow('list-query/microseconds?paging[size]=1', 'after_url', maxPages: count($ids) + 1);

        $this->assertSame($ids, $forward['ids']);
    }

    #[Test]
    public function it_walks_an_unordered_list_by_its_key(): void
    {
        $ids = $this->createModelsAt(Rfc3339ExtendedTestModel::class, '2026-10-25T21:00:00.100+00:00', '2026-10-25T21:00:00.400+00:00');

        sort($ids);

        $forward = $this->follow('list-query/unordered?paging[size]=1', 'after_url', maxPages: count($ids) + 1);

        $this->assertSame($ids, $forward['ids']);
    }

    #[DataProvider('cursorsThisListDidNotIssue')]
    #[Test]
    public function it_answers_an_after_cursor_this_list_did_not_issue_with_422(string $cursor): void
    {
        $this->getJson('list-query/rfc3339?'.Arr::query(['paging' => ['after' => $cursor]]))
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['paging.after']);
    }

    #[DataProvider('cursorsThisListDidNotIssue')]
    #[Test]
    public function it_answers_a_before_cursor_this_list_did_not_issue_with_422(string $cursor): void
    {
        $this->getJson('list-query/rfc3339?'.Arr::query(['paging' => ['before' => $cursor]]))
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['paging.before']);
    }

    #[Test]
    public function it_answers_a_date_that_does_not_exist_in_the_models_own_format_with_422(): void
    {
        $cursor = (new Cursor(['created_at' => '2026-02-31 00:00:00.000000', 'id' => self::ID]))->encode();

        $this->getJson('list-query/microseconds?'.Arr::query(['paging' => ['after' => $cursor]]))
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['paging.after']);
    }

    #[Test]
    public function it_answers_both_directions_with_422(): void
    {
        $cursor = (new Cursor(['created_at' => '2026-10-25T21:00:00.100+00:00', 'id' => self::ID]))->encode();

        $this->getJson('list-query/rfc3339?'.Arr::query(['paging' => ['after' => $cursor, 'before' => $cursor]]))
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['paging.before']);
    }

    #[DataProvider('sizesOutOfRange')]
    #[Test]
    public function it_answers_a_size_out_of_range_with_422(string $size): void
    {
        $this->getJson('list-query/rfc3339?'.Arr::query(['paging' => ['size' => $size]]))
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['paging.size']);
    }

    #[Test]
    public function it_applies_a_page_size_of_ten_by_default(): void
    {
        $this->createModelsAt(Rfc3339ExtendedTestModel::class, ...array_map(
            fn (int $millisecond): string => sprintf('2026-10-25T21:00:00.%03d+00:00', $millisecond),
            range(1, 11),
        ));

        $this->getJson('list-query/rfc3339')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.paging.size', 10);
    }

    #[Test]
    public function it_validates_paging_from_the_query_string_only(): void
    {
        $this->json('GET', 'list-query/rfc3339', ['paging' => ['size' => 1000000, 'after' => 'not-a-cursor']])
            ->assertOk();
    }

    #[Test]
    public function it_sizes_the_page_from_the_query_string_only(): void
    {
        $this->createModelsAt(Rfc3339ExtendedTestModel::class, '2026-10-25T21:00:00.100+00:00', '2026-10-25T21:00:00.400+00:00');

        $this->json('GET', 'list-query/rfc3339', ['paging' => ['size' => 1]])
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function it_ignores_paging_cursor(): void
    {
        $ids = $this->createModelsAt(Rfc3339ExtendedTestModel::class, '2026-10-25T21:00:00.100+00:00', '2026-10-25T21:00:00.400+00:00');

        $cursor = (new Cursor(['created_at' => '2026-10-25T21:00:00.100+00:00', 'id' => $ids[0]]))->encode();

        $this->getJson('list-query/rfc3339?'.Arr::query(['paging' => ['size' => 1, 'cursor' => $cursor]]))
            ->assertOk()
            ->assertJsonPath('data.*.id', [$ids[0]]);
    }

    #[Test]
    public function it_takes_the_direction_from_the_key_not_the_cursor(): void
    {
        $ids = $this->createModelsAt(
            Rfc3339ExtendedTestModel::class,
            '2026-10-25T21:00:00.100+00:00',
            '2026-10-25T21:00:00.400+00:00',
            '2026-10-25T21:00:00.700+00:00',
        );

        $cursor = (new Cursor(['created_at' => '2026-10-25T21:00:00.400+00:00', 'id' => $ids[1]], false))->encode();

        $this->getJson('list-query/rfc3339?'.Arr::query(['paging' => ['size' => 1, 'after' => $cursor]]))
            ->assertOk()
            ->assertJsonPath('data.*.id', [$ids[2]]);
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

    /**
     * @return array{ids: list<string>, last: array<string, mixed>}
     */
    private function follow(string $url, string $link, int $maxPages): array
    {
        $ids = [];
        $paging = [];

        foreach (range(1, $maxPages) as $_) {
            $response = $this->getJson($url)->assertOk();

            $ids = [...$ids, ...$response->json('data.*.id')];
            $paging = $response->json('meta.paging');
            $url = $paging[$link];

            if ($url === null) {
                break;
            }
        }

        return ['ids' => $ids, 'last' => $paging];
    }
}
