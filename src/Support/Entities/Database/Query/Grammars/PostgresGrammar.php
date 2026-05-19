<?php

declare(strict_types=1);

namespace Support\Entities\Database\Query\Grammars;

use Illuminate\Database\Query\Grammars\PostgresGrammar as BasePostgresGrammar;
use Support\Entities\Database\Query\Grammars\Concerns\ReadsDateFormatFromConfig;

class PostgresGrammar extends BasePostgresGrammar
{
    use ReadsDateFormatFromConfig;
}
