<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Errors;

use Closure;
use Illuminate\Validation\Validator as IlluminateValidator;
use Support\Http\Validator as BaseValidator;

final class AfterValidator extends BaseValidator
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required'],
        ];
    }

    /**
     * @return array<int, Closure(IlluminateValidator): void>
     */
    public function after(): array
    {
        return [
            function (IlluminateValidator $validator): void {
                $validator->errors()->add('window', 'The booking window is closed.');
            },
        ];
    }
}
