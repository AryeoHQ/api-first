<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Errors;

use Illuminate\Contracts\Validation\ValidationRule;
use Support\Http\Validator as BaseValidator;

final class RulesValidator extends BaseValidator
{
    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:3'],
            'tags' => ['sometimes', 'array', 'min:2'],
            'photo' => ['sometimes', 'file', 'max:1'],
            'items.*.name' => ['required'],
            'a/b' => ['sometimes', 'integer'],
            'page.size' => ['sometimes', new PageSize],
        ];
    }
}
