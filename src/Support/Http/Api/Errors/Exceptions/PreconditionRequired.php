<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors\Exceptions;

use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;

final class PreconditionRequired extends PreconditionRequiredHttpException {}
