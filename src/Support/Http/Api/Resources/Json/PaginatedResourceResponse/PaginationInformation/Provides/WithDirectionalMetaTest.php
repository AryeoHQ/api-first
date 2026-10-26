<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Provides;

use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Support\Routing\RouteRegistrar;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;
use Tests\Fixtures\Support\Http\Api\Request\ListQuery\Controller;
use Tests\TestCase;

#[CoversTrait(WithDirectionalMeta::class)]
final class WithDirectionalMetaTest extends TestCase
{
    /**
     * @param  \Illuminate\Routing\Router  $router
     */
    protected function defineRoutes($router): void
    {
        app(RouteRegistrar::class)->registerFile((string) (new ReflectionClass(Controller::class))->getFileName());
    }

    #[Test]
    public function it_links_the_next_page_with_a_url_that_repeats_the_query(): void
    {
        $this->createModelsAt('2026-10-25T21:00:00.100+00:00', '2026-10-25T21:00:00.400+00:00');

        $response = $this->getJson('list-query/rfc3339?'.Arr::query([
            'paging' => ['size' => 1],
            'sort' => 'created_at',
            'filters' => ['status' => 'active'],
        ]))->assertOk();

        $after = $response->json('meta.paging.after');

        $this->assertSame([
            'filters' => ['status' => 'active'],
            'paging' => [
                'before' => null,
                'before_url' => null,
                'after' => $after,
                'after_url' => "http://localhost/list-query/rfc3339?paging%5Bsize%5D=1&paging%5Bafter%5D={$after}&sort=created_at&filters%5Bstatus%5D=active",
                'size' => 1,
            ],
            'sort' => 'created_at',
        ], $response->json('meta'));
    }

    #[Test]
    public function it_replaces_the_requested_cursor_in_each_url(): void
    {
        $this->createModelsAt('2026-10-25T21:00:00.100+00:00', '2026-10-25T21:00:00.400+00:00', '2026-10-25T21:00:00.700+00:00');

        $cursor = $this->getJson('list-query/rfc3339?'.Arr::query(['paging' => ['size' => 1]]))->assertOk()->json('meta.paging.after');

        $response = $this->getJson('list-query/rfc3339?'.Arr::query(['paging' => ['size' => 1, 'after' => $cursor]]))->assertOk();

        $before = $response->json('meta.paging.before');
        $after = $response->json('meta.paging.after');

        $this->assertSame([
            'before' => $before,
            'before_url' => "http://localhost/list-query/rfc3339?paging%5Bsize%5D=1&paging%5Bbefore%5D={$before}",
            'after' => $after,
            'after_url' => "http://localhost/list-query/rfc3339?paging%5Bsize%5D=1&paging%5Bafter%5D={$after}",
            'size' => 1,
        ], $response->json('meta.paging'));
    }

    #[Test]
    public function it_returns_null_paging_when_one_page_holds_the_list(): void
    {
        $this->createModelsAt('2026-10-25T21:00:00.100+00:00');

        $this->getJson('list-query/rfc3339')
            ->assertOk()
            ->assertExactJson([
                'data' => [['id' => Rfc3339ExtendedTestModel::query()->value('id'), 'resource_type' => 'list_query_item']],
                'meta' => ['filters' => null, 'paging' => null, 'sort' => null],
            ]);
    }

    /**
     * @return list<string>
     */
    private function createModelsAt(string ...$timestamps): array
    {
        return array_map(
            fn (string $timestamp): string => Rfc3339ExtendedTestModel::query()->forceCreate(['created_at' => $timestamp])->getKey(),
            array_values($timestamps),
        );
    }
}
