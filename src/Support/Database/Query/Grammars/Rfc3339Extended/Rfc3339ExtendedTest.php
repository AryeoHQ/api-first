<?php

declare(strict_types=1);

namespace Support\Database\Query\Grammars\Rfc3339Extended;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;
use Tests\TestCase;

final class Rfc3339ExtendedTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        Schema::create('rfc3339_extended_test_models', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestampsTz();
        });
    }

    #[Test]
    public function it_round_trips_timestamps_with_millisecond_precision(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-19T10:30:45.123+00:00'));

        $model = Rfc3339ExtendedTestModel::create();
        $raw = DB::table('rfc3339_extended_test_models')->where('id', $model->id)->first();

        $this->assertSame('2026-05-19T10:30:45.123+00:00', $raw->created_at);
    }
}
