<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Stringable;

final class Sort implements Castable, Stringable
{
    public function __construct(
        public string $field
    ) {}

    /**
     * @param  string[]  $arguments
     * @return class-string<CastsAttributes<Sort, Sort|mixed>>
     */
    public static function castUsing(array $arguments): string
    {
        return SortCast::class;
    }

    public function __toString(): string
    {
        return $this->field;
    }
}
