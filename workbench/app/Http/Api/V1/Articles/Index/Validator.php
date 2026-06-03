<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles\Index;

use Support\Http\Casts\Nested;
use Support\Http\Requests\Contracts\CastableData;
use Support\Http\Requests\Factories\Attributes\UseFactory;
use Support\Http\Requests\Factories\Provides\HasFactory;
use Support\Http\Requests\Provides\CastsData;
use Support\Http\Validator as BaseValidator;
use Support\Primitives\Interval;
use Support\Primitives\Sort;

#[UseFactory(Factory::class)]
final class Validator extends BaseValidator implements CastableData
{
    use CastsData;
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function casts(): array
    {
        return [
            'sort' => Sort::class,
            'filters' => Nested::make([
                'syndicated_between' => Interval::class,
            ]),
        ];
    }
}
