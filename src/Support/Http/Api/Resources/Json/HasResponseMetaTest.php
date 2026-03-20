<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json;

use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Http\Api\Resources\Json\ExampleResource;
use Tests\TestCase;

#[CoversClass(HasResponseMeta::class)]
final class HasResponseMetaTest extends TestCase
{
    #[Test]
    public function it_includes_null_meta_on_single_resource_response(): void
    {
        $resource = new ExampleResource(['id' => 1]);

        $response = $resource->toResponse(new Request);

        $this->assertSame([
            'data' => ['id' => 1],
            'meta' => [
                'paging' => null,
                'filters' => null,
                'sort' => null,
            ],
        ], $response->getData(assoc: true));
    }

    #[Test]
    public function it_includes_null_meta_on_non_paginated_collection_response(): void
    {
        $response = ExampleResource::collection([['id' => 1]])
            ->toResponse(new Request);

        $this->assertSame([
            'data' => [['id' => 1]],
            'meta' => [
                'paging' => null,
                'filters' => null,
                'sort' => null,
            ],
        ], $response->getData(assoc: true));
    }

    #[Test]
    public function it_defers_to_pagination_information_on_paginated_collection(): void
    {
        $paginator = new CursorPaginator(
            items: [['id' => 1]],
            perPage: 1,
        );

        $response = ExampleResource::collection($paginator)
            ->toResponse(new Request);

        $data = $response->getData(assoc: true);

        $this->assertArrayHasKey('meta', $data);
        $this->assertNull($data['meta']['paging']);
        $this->assertNull($data['meta']['filters']);
        $this->assertNull($data['meta']['sort']);
        $this->assertArrayNotHasKey(0, $data['meta']['paging'] ?? []);
    }
}
