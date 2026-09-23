<?php

declare(strict_types=1);

namespace Support\Database\Query\Grammars\Rfc3339Extended;

use Support\Database\Query\Grammars\Rfc3339Extended\Concerns\EnforcesDateFormat;

final class MariaDbGrammar extends \Illuminate\Database\Query\Grammars\MariaDbGrammar
{
    use EnforcesDateFormat;
}
