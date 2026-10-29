<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

enum ErrorCode: string
{
    case Error = 'error';

    case Authentication = 'authentication';

    case Authorization = 'authorization';

    case Validation = 'validation';

    public static function fromStatus(int $status): self
    {
        return match ($status) {
            401 => self::Authentication,
            403 => self::Authorization,
            422 => self::Validation,
            default => self::Error,
        };
    }
}
