<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse;

use Illuminate\Contracts\Database\Eloquent\Castable;

final class StringableSort implements Castable
{
    public function __construct(
        public string $field
    ) {
    }

    public static function castUsing(array $arguments): string
    {
        return StringableSortCast::class;
    }

    public function __toString(): string
    {
        return $this->field;
    }
}
