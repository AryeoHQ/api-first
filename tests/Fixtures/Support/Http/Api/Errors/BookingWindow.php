<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Errors;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class BookingWindow implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fail('The booking window is closed.');
    }
}
