<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

final class Placeholder
{
    public const string DETAIL = 'The request failed for a reason this API does not catalogue yet.';

    private const array CATEGORIES = [
        400 => 'request',
        401 => 'auth',
        403 => 'permission',
        404 => 'not_found',
        405 => 'method',
        409 => 'conflict',
        412 => 'precondition',
        415 => 'media_type',
        422 => 'validation',
        428 => 'precondition',
        429 => 'rate_limit',
        500 => 'server',
        503 => 'server',
    ];

    public static function code(int $status): string
    {
        $category = self::CATEGORIES[$status] ?? ($status >= 500 ? 'server' : 'request');

        return "{$category}.unspecified";
    }
}
