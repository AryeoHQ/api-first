<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Errors;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;
use Support\Http\Validator as BaseValidator;

final class UncataloguedValidator extends BaseValidator
{
    /**
     * @return array<string, array<int, string|ValidationRule|Password|Closure>>
     */
    public function rules(): array
    {
        return [
            'password' => ['sometimes', Password::min(8)],
            'nickname' => ['sometimes', function (string $attribute, mixed $value, Closure $fail): void {
                $fail('The nickname is taken.');
            }],
            'window' => ['sometimes', new BookingWindow],
        ];
    }
}
