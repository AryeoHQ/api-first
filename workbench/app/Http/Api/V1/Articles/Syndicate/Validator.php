<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles\Syndicate;

use Support\Http\Validator as BaseValidator;

final class Validator extends BaseValidator
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
