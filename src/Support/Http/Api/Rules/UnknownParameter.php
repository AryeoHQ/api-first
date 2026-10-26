<?php

declare(strict_types=1);

namespace Support\Http\Api\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class UnknownParameter implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fail('The :attribute is not a parameter this list accepts.');
    }
}
