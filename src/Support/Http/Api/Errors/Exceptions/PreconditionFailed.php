<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors\Exceptions;

use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

final class PreconditionFailed extends PreconditionFailedHttpException
{
    public function __construct(string $etag)
    {
        parent::__construct(headers: ['ETag' => $etag]);
    }
}
