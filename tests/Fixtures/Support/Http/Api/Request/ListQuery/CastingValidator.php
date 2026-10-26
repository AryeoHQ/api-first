<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Request\ListQuery;

use Support\Http\Api\Request\Concerns\PagesByCursor;
use Support\Http\Requests\Provides\CastsData;
use Support\Http\Validator as BaseValidator;

final class CastingValidator extends BaseValidator
{
    use CastsData;
    use PagesByCursor;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->pagingRules();
    }
}
