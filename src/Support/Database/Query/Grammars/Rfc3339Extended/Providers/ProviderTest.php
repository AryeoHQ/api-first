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
use Support\Database\Query\Grammars\Rfc3339Extended\Listeners\SwapGrammar;
use Support\Database\Query\Grammars\Rfc3339Extended\SQLiteGrammar;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;
use Tests\TestCase;

#[CoversClass(Provider::class)]
final class ProviderTest extends TestCase
{
    #[Test]
    public function it_registers_the_grammar_swap_listener(): void
    {
        $this->assertContains(
            SwapGrammar::class,
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
    public function it_serializes_carbon_with_millisecond_precision(): void
    {
        $timestamp = '2026-05-19T10:30:45.123+00:00';

        $this->assertSame(
            $timestamp,
            Date::parse($timestamp)->jsonSerialize(),
        );
    }

    #[Test]
    public function it_stores_timestamps_with_millisecond_precision(): void
    {
        $timestamp = '2026-05-19T10:30:45.123+00:00';

        Date::withTestNow(Date::parse($timestamp), function () use ($timestamp) {
            $model = Rfc3339ExtendedTestModel::factory()->create();
            $raw = DB::table('rfc3339_extended_test_models')->where('id', $model->getKey())->first();

            $this->assertSame($timestamp, $raw->created_at);
        });
    }

    #[Test]
    public function it_retrieves_model_timestamps_with_millisecond_precision(): void
    {
        $timestamp = '2026-05-19T10:30:45.123+00:00';

        Date::withTestNow(Date::parse($timestamp), function () use ($timestamp) {
            $model = Rfc3339ExtendedTestModel::factory()->create();

            $this->assertSame($timestamp, $model->fresh()->created_at->jsonSerialize());
        });
    }
}
