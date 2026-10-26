<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Request\ListQuery;

use Support\Http\Api\Request\Concerns\PagesByCursor;
use Support\Http\Validator as BaseValidator;

final class Validator extends BaseValidator
{
    use PagesByCursor;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->pagingRules();
    }
}
