<?php

declare(strict_types=1);

namespace Support\Database\Query\Grammars\Rfc3339Extended\Providers;

use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Database\Query\Grammars\Rfc3339Extended\Listeners\SwapDateFormatGrammar;
use Support\Database\Query\Grammars\Rfc3339Extended\SQLiteGrammar;
use Tests\TestCase;

#[CoversClass(Provider::class)]
final class ProviderTest extends TestCase
{
    #[Test]
    public function it_registers_the_grammar_swap_listener(): void
    {
        $this->assertContains(
            SwapDateFormatGrammar::class,
            Event::getRawListeners()[ConnectionEstablished::class],
        );
    }

    #[Test]
    public function it_registers_the_listener_before_the_default_connection_is_established(): void
    {
        $this->assertInstanceOf(
            SQLiteGrammar::class,
            DB::connection()->getQueryGrammar(),
        );
    }

    #[Test]
    public function it_sets_default_time_precision_to_three(): void
    {
        $this->assertSame(3, Builder::$defaultTimePrecision);
    }

    #[Test]
    public function it_configures_carbon_serialization_to_rfc3339_extended(): void
    {
        $this->assertSame(
            '2026-05-19T10:30:45.123+00:00',
            Date::parse('2026-05-19T10:30:45.123+00:00')->jsonSerialize(),
        );
    }
}
