<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse;

use Illuminate\Foundation\Http\FormRequest;
use Support\Http\Requests\Contracts\CastableData;
use Support\Http\Requests\Provides\CastsData;

final class StringableFormRequest extends FormRequest implements CastableData
{
    use CastsData;

    public function casts(): array
    {
        return [
            'sort' => StringableSort::class,
        ];
    }
}
