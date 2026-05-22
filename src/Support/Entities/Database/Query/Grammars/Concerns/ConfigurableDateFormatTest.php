<?php

declare(strict_types=1);

namespace Support\Entities\Database\Query\Grammars\Concerns;

use DateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use Support\Entities\Database\Query\Grammars\PostgresGrammar;
use Support\Entities\Database\Query\Grammars\SQLiteGrammar;
use Tests\Fixtures\Support\Entities\Posts\Post;
use Tests\TestCase;

#[CoversClass(PostgresGrammar::class)]
#[CoversClass(SQLiteGrammar::class)]
#[CoversTrait(WithConfigurableDateFormat::class)]
#[WithConfig('database.connections.testing.date_format', 'Y-m-d\TH:i:s.vP')]
final class ConfigurableDateFormatTest extends TestCase
{
    #[Test]
    public function it_uses_the_configured_date_format(): void
    {
        $this->assertSame(DateTime::RFC3339_EXTENDED, DB::connection()->getQueryGrammar()->getDateFormat());
    }

    #[Test]
    public function it_writes_timestamps_in_the_configured_format(): void
    {
        Date::setTestNow(Date::parse('2026-05-19T10:30:45.123+00:00')->toImmutable());

        $model = Post::create();

        $raw = DB::table('posts')->where('id', $model->id)->first();

        $this->assertSame('2026-05-19T10:30:45.123+00:00', $raw->created_at);
        $this->assertSame('2026-05-19T10:30:45.123+00:00', $raw->updated_at);
    }

    #[Test]
    public function it_reads_timestamps_back_as_carbon_instances_with_millisecond_precision(): void
    {
        Date::setTestNow(Date::parse('2026-05-19T10:30:45.123+00:00')->toImmutable());

        $model = Post::create()->fresh();

        $this->assertInstanceOf(Carbon::class, $model->created_at);
        $this->assertSame('2026-05-19T10:30:45.123+00:00', $model->created_at->format(DateTime::RFC3339_EXTENDED));
    }

    #[Test]
    public function it_queries_with_dates_using_the_configured_format(): void
    {
        Date::setTestNow(Date::parse('2026-05-19T10:30:45.123+00:00')->toImmutable());
        $earlier = Post::create();

        Date::setTestNow(Date::parse('2026-05-19T12:00:00.456+00:00')->toImmutable());
        $later = Post::create();

        $results = Post::query()
            ->where('created_at', '>', Date::parse('2026-05-19T11:00:00.000+00:00')->toImmutable())
            ->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($later));
    }
}
