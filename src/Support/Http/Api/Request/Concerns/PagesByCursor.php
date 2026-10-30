<?php

declare(strict_types=1);

namespace Support\Http\Api\Request\Concerns;

use Illuminate\Foundation\Http\FormRequest;
use Support\Http\Api\Pagination\CursorResolver;
use Support\Http\Api\Rules\Cursor;

/**
 * @phpstan-require-extends FormRequest
 */
trait PagesByCursor
{
    /**
     * @return array<string, mixed>
     */
    public function pagingRules(): array
    {
        return CursorResolver::rules(new Cursor);
    }
}
