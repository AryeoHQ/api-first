<?php

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan;

use Illuminate\Foundation\Http\FormRequest;

final class Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
