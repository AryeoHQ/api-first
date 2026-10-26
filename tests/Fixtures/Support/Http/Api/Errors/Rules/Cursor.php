<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Errors\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class Cursor implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fail('The :attribute is not a valid cursor.');
    }
}
