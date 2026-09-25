<?php

declare(strict_types=1);

namespace Support\Database\Query\Grammars\Rfc3339Extended\Concerns;

use DateTime;

trait EnforcesDateFormat
{
    public function getDateFormat(): string
    {
        return DateTime::RFC3339_EXTENDED;
    }
}
