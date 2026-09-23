<?php

declare(strict_types=1);

namespace Support\Database\Query\Grammars\Rfc3339Extended\Concerns;

use DateTime;
use Illuminate\Database\Grammar;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** @mixin TestCase */
trait EnforcesDateFormatTestCases
{
    /** @return class-string<Grammar> */
    abstract protected function grammar(): string;

    #[Test]
    public function it_uses_the_rfc3339_extended_date_format(): void
    {
        $grammar = $this->grammar();

        $this->assertSame(
            DateTime::RFC3339_EXTENDED,
            (new $grammar(DB::connection()))->getDateFormat(),
        );
    }
}
