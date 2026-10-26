<?php

declare(strict_types=1);

namespace Support\Http\Api\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

final class ProhibitsParameter implements DataAwareRule, ValidationRule
{
    private string $parameter;

    /**
     * @var array<array-key, mixed>
     */
    private array $data = [];

    public function __construct(string $parameter)
    {
        $this->parameter = $parameter;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (filled($value) && filled(Arr::get($this->data, $this->parameter))) {
            $fail('The :attribute cannot be sent with '.preg_replace('/\.([^.]+)/', '[$1]', $this->parameter).'.');
        }
    }
}
