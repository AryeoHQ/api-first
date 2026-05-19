<?php

declare(strict_types=1);

namespace Support\Entities\Database\Query\Grammars;

use Illuminate\Database\Query\Grammars\SQLiteGrammar as BaseSQLiteGrammar;
use Support\Entities\Database\Query\Grammars\Concerns\ReadsDateFormatFromConfig;

class SQLiteGrammar extends BaseSQLiteGrammar
{
    use ReadsDateFormatFromConfig;
}
