<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;

trait HasResponseMeta
{
    /** @return array<string, mixed> */
    public function with(Request $request): array
    {
        if ($this->resource instanceof AbstractPaginator || $this->resource instanceof AbstractCursorPaginator) {
            return [];
        }

        return [
            'meta' => [
                'paging' => null,
                'filters' => null,
                'sort' => null,
            ],
        ];
    }

    /** @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection */
    protected static function newCollection(mixed $resource): AnonymousResourceCollection
    {
        return new class ($resource, static::class) extends AnonymousResourceCollection
        {
            use HasResponseMeta;
        };
    }
}
