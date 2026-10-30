<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Errors;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PageSize implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]) === false) {
            $fail('The :attribute must be between 1 and 100.');
        }
    }
}
