<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles\Store;

use Support\Http\Requests\Factories\Attributes\UseFactory;
use Support\Http\Requests\Factories\Provides\HasFactory;

#[UseFactory(Factory::class)]
final class Validator extends \Support\Http\Validator
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'body' => ['required', 'string'],
        ];
    }
}
