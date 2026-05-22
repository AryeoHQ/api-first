<?php

declare(strict_types=1);

namespace Support\Entities\Database\Query\Grammars;

use Support\Entities\Database\Query\Grammars\Concerns\WithConfigurableDateFormat;

class SQLiteGrammar extends \Illuminate\Database\Query\Grammars\SQLiteGrammar
{
    use WithConfigurableDateFormat;
}
