<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class ParentsChanged extends ConflictHttpException {}
