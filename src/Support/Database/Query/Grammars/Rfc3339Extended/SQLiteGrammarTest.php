<?php

declare(strict_types=1);

namespace Support\Database\Query\Grammars\Rfc3339Extended;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use Support\Database\Query\Grammars\Rfc3339Extended\Concerns\EnforcesDateFormat;
use Support\Database\Query\Grammars\Rfc3339Extended\Concerns\EnforcesDateFormatTestCases;
use Tests\TestCase;

#[CoversClass(SQLiteGrammar::class)]
#[CoversTrait(EnforcesDateFormat::class)]
final class SQLiteGrammarTest extends TestCase
{
    use EnforcesDateFormatTestCases;

    protected function grammar(): string
    {
        return SQLiteGrammar::class;
    }
}
