<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse;

use Illuminate\Foundation\Http\FormRequest;
use Support\Http\Casts\Nested;
use Support\Http\Requests\Contracts\CastableData;
use Support\Http\Requests\Provides\CastsData;
use Support\Primitives\Sort;

final class CastableFormRequest extends FormRequest implements CastableData
{
    use CastsData;

    public function casts(): array
    {
        return [
            'filters' => Nested::make([
                'is_active' => 'boolean',
                'count' => 'integer',
            ]),
            'sort' => Sort::class,
        ];
    }
}
