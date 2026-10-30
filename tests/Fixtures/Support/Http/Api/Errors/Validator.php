<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Errors;

use Support\Http\Validator as BaseValidator;

final class Validator extends BaseValidator
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'photo_count' => ['integer', 'min:0'],
            'filters.status' => ['in:draft,active'],
        ];
    }
}
