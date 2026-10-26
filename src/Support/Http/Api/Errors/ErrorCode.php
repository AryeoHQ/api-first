<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

enum ErrorCode: string
{
    case InvalidToken = 'auth.invalid_token';
    case MissingToken = 'auth.missing_token';
    case UnsupportedMediaType = 'media_type.unsupported';
    case MethodNotAllowed = 'method.not_allowed';
    case ResourceNotFound = 'not_found.resource';
    case PermissionDenied = 'permission.denied';
    case MissingScope = 'permission.missing_scope';
    case PreconditionFailed = 'precondition.failed';
    case PreconditionRequired = 'precondition.required';
    case RateLimitExceeded = 'rate_limit.exceeded';
    case MalformedRequest = 'request.malformed';
    case InternalError = 'server.internal_error';
    case Unavailable = 'server.unavailable';
}
