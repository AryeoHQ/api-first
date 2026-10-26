<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions\Exceptions;

use LogicException;

final class UnloadedConcurrencyRevision extends LogicException {}
