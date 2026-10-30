<?php

declare(strict_types=1);

namespace Support\Http\Api\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PageSize implements ValidationRule
{
    private const int MAX = 100;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $size = is_int($value) || (is_string($value) && preg_match('/\A[0-9]+\z/', $value) === 1) ? (int) $value : 0;

        if ($size < 1 || $size > self::MAX) {
            $fail('The :attribute must be an integer from 1 to '.self::MAX.'.');
        }
    }
}
