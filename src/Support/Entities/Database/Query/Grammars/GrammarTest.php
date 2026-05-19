<?php

declare(strict_types=1);

namespace Support\Entities\Database\Query\Grammars;

use Carbon\CarbonImmutable;
use DateTime;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Entities\Database\Query\Grammars\Concerns\ReadsDateFormatFromConfig;
use Tests\TestCase;

#[CoversClass(PostgresGrammar::class)]
#[CoversClass(SQLiteGrammar::class)]
#[CoversClass(ReadsDateFormatFromConfig::class)]
#[WithConfig('database.connections.testing.date_format', 'Y-m-d\TH:i:s.vP')]
final class GrammarTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        Schema::create('grammar_test_models', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestampsTz();
        });
    }

    #[Test]
    public function it_swaps_the_grammar_when_date_format_is_configured(): void
    {
        $this->assertInstanceOf(SQLiteGrammar::class, DB::connection()->getQueryGrammar());
    }

    #[Test]
    public function it_uses_the_configured_date_format(): void
    {
        $this->assertSame(DateTime::RFC3339_EXTENDED, DB::connection()->getQueryGrammar()->getDateFormat());
    }

    #[Test]
    public function it_writes_timestamps_in_the_configured_format(): void
    {
        Carbon::setTestNow(CarbonImmutable::parse('2026-05-19T10:30:45.123+00:00'));

        $model = $this->createModel();

        $raw = DB::table('grammar_test_models')->where('id', $model->id)->first();

        $this->assertSame('2026-05-19T10:30:45.123+00:00', $raw->created_at);
        $this->assertSame('2026-05-19T10:30:45.123+00:00', $raw->updated_at);
    }

    #[Test]
    public function it_reads_timestamps_back_as_carbon_instances_with_millisecond_precision(): void
    {
        Carbon::setTestNow(CarbonImmutable::parse('2026-05-19T10:30:45.123+00:00'));

        $model = $this->createModel()->fresh();

        $this->assertInstanceOf(Carbon::class, $model->created_at);
        $this->assertSame('2026-05-19T10:30:45.123+00:00', $model->created_at->format(DateTime::RFC3339_EXTENDED));
    }

    #[Test]
    public function it_queries_with_dates_using_the_configured_format(): void
    {
        Carbon::setTestNow(CarbonImmutable::parse('2026-05-19T10:30:45.123+00:00'));
        $earlier = $this->createModel();

        Carbon::setTestNow(CarbonImmutable::parse('2026-05-19T12:00:00.456+00:00'));
        $later = $this->createModel();

        $results = $this->newModel()
            ->where('created_at', '>', CarbonImmutable::parse('2026-05-19T11:00:00.000+00:00'))
            ->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($later));
    }

    private function createModel(): Model
    {
        return $this->newModel()->create();
    }

    private function newModel(): Model
    {
        return new class extends Model
        {
            use HasUuids;

            protected $table = 'grammar_test_models';
        };
    }
}
