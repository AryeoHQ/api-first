<?php

declare(strict_types=1);

namespace Support\Http\Api\Request\Concerns;

use Illuminate\Pagination\Cursor;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Support\Routing\RouteRegistrar;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;
use Tests\Fixtures\Support\Http\Api\Request\ListQuery\CastingController;
use Tests\Fixtures\Support\Http\Api\Request\ListQuery\Controller;
use Tests\TestCase;

#[CoversTrait(PagesByCursor::class)]
final class PagesByCursorTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>|string, string}>
     */
    public static function pagingErrors(): array
    {
        $cursor = (new Cursor(['created_at' => '2026-10-25T21:00:00.100+00:00', 'id' => '0199f7a4-0000-7000-8000-000000000000']))->encode().'.mac';

        return [
            'a size out of range' => [['size' => '0'], 'paging.size'],
            'an undecodable after cursor' => [['after' => 'not-a-cursor'], 'paging.after'],
            'an undecodable before cursor' => [['before' => 'not-a-cursor'], 'paging.before'],
            'both directions' => [['after' => $cursor, 'before' => $cursor], 'paging.before'],
            'an unknown paging key' => [['offset' => '20'], 'paging.offset'],
            'a paging value that is not an array' => ['x', 'paging'],
        ];
    }

    /**
     * @param  \Illuminate\Routing\Router  $router
     */
    protected function defineRoutes($router): void
    {
        app(RouteRegistrar::class)->registerFile((string) (new ReflectionClass(Controller::class))->getFileName());
        app(RouteRegistrar::class)->registerFile((string) (new ReflectionClass(CastingController::class))->getFileName());
    }

    /**
     * @param  array<string, mixed>|string  $paging
     */
    #[DataProvider('pagingErrors')]
    #[Test]
    public function it_batches_a_paging_error_with_other_field_errors(array|string $paging, string $key): void
    {
        $this->getJson('list-query/rfc3339?'.Arr::query(['sort' => 'name', 'paging' => $paging]))
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['sort', $key]);
    }

    #[Test]
    public function it_validates_paging_from_what_validation_data_returns(): void
    {
        $this->json('GET', 'list-query-casting', ['paging' => ['size' => 101]])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['paging.size']);
    }

    #[Test]
    public function it_answers_an_unsigned_cursor_on_the_validator_route_with_422(): void
    {
        $id = Rfc3339ExtendedTestModel::query()->forceCreate(['created_at' => '2026-10-25T21:00:00.100+00:00'])->getKey();

        $cursor = (new Cursor(['created_at' => '2026-10-25T21:00:00.100+00:00', 'id' => $id]))->encode().'.unsigned';

        $this->getJson('list-query/rfc3339?'.Arr::query(['paging' => ['after' => $cursor]]))
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['paging.after']);
    }

    #[Test]
    public function it_accepts_the_cursors_this_list_signed(): void
    {
        $ids = array_map(
            fn (string $timestamp): string => Rfc3339ExtendedTestModel::query()->forceCreate(['created_at' => $timestamp])->getKey(),
            ['2026-10-25T21:00:00.100+00:00', '2026-10-25T21:00:00.400+00:00', '2026-10-25T21:00:00.700+00:00'],
        );

        $after = $this->getJson('list-query/rfc3339?paging[size]=1')->assertOk()->json('meta.paging.after_url');
        $before = $this->getJson($after)->assertOk()->assertJsonPath('data.*.id', [$ids[1]])->json('meta.paging.before_url');

        $this->getJson($before)
            ->assertOk()
            ->assertJsonPath('data.*.id', [$ids[0]]);
    }
}
