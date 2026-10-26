<?php

declare(strict_types=1);

namespace Support\Http\Api\Rules;

use Illuminate\Http\Request;
use Illuminate\Pagination\Cursor as PaginationCursor;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;
use Tests\TestCase;

#[CoversClass(Cursor::class)]
final class CursorTest extends TestCase
{
    /**
     * @return array<string, array{mixed}>
     */
    public static function valuesThatAreNotCursors(): array
    {
        return [
            'an undecodable string' => ['not-a-cursor'],
            'a cursor with a character outside base64url' => [(new PaginationCursor(['id' => 1]))->encode().'!'],
            'an encoded scalar' => [rtrim(base64_encode('1'), '=')],
            'an encoded object without a direction' => [rtrim(base64_encode('{"created_at":"2026-10-25 21:00:00"}'), '=')],
            'a list' => [['not-a-cursor']],
            'a parameter that is not a scalar' => [(new PaginationCursor(['created_at' => ['2026-10-25 21:00:00']]))->encode()],
            'a null parameter' => [(new PaginationCursor(['created_at' => null]))->encode()],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function impossibleDates(): array
    {
        return [
            'seconds' => ['2026-02-31 00:00:00'],
            'milliseconds' => ['2026-02-31T00:00:00.000+00:00'],
            'microseconds' => ['2026-02-31T00:00:00.000000+00:00'],
            'an hour past midnight' => ['2026-10-25 24:00:00'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function possibleDates(): array
    {
        return [
            'seconds' => ['2026-10-25 21:00:00'],
            'milliseconds' => ['2026-10-25T21:00:00.100+00:00'],
            'microseconds' => ['2026-10-25T21:00:00.123456+00:00'],
        ];
    }

    #[Test]
    public function it_accepts_a_cursor_that_cursor_pagination_issued(): void
    {
        Rfc3339ExtendedTestModel::factory()->create(['created_at' => '2026-10-25T21:00:00.100+00:00']);
        Rfc3339ExtendedTestModel::factory()->create(['created_at' => '2026-10-25T21:00:00.400+00:00']);

        $this->app->instance('request', Request::create('/test', 'GET'));

        $cursor = Rfc3339ExtendedTestModel::query()->orderBy('created_at')->orderBy('id')->cursorPaginate(1)->nextCursor()?->encode();

        $this->assertTrue($this->passes($cursor, new Cursor(['created_at', 'id'])));
    }

    #[DataProvider('valuesThatAreNotCursors')]
    #[Test]
    public function it_fails_a_value_that_is_not_a_cursor(mixed $value): void
    {
        $this->assertFalse($this->passes($value));
    }

    #[DataProvider('impossibleDates')]
    #[Test]
    public function it_fails_an_impossible_date_in_a_supported_format(string $date): void
    {
        $this->assertFalse($this->passes((new PaginationCursor(['created_at' => $date]))->encode()));
    }

    #[DataProvider('possibleDates')]
    #[Test]
    public function it_accepts_a_possible_date_in_each_supported_format(string $date): void
    {
        $this->assertTrue($this->passes((new PaginationCursor(['created_at' => $date]))->encode()));
    }

    #[Test]
    public function it_accepts_a_string_that_no_supported_format_parses(): void
    {
        $this->assertTrue($this->passes((new PaginationCursor(['title' => '2026-02-31 launch notes', 'created_at' => '2026-02-31 00:00:00.000000']))->encode()));
    }

    #[Test]
    public function it_fails_an_impossible_date_in_a_given_format(): void
    {
        $this->assertFalse($this->passes(
            (new PaginationCursor(['created_at' => '2026-02-31 00:00:00.000000']))->encode(),
            new Cursor(dateFormats: ['Y-m-d H:i:s.u']),
        ));
    }

    #[Test]
    public function it_accepts_a_possible_date_in_a_given_format(): void
    {
        $this->assertTrue($this->passes(
            (new PaginationCursor(['created_at' => '2026-10-25 21:00:00.100000']))->encode(),
            new Cursor(dateFormats: ['Y-m-d H:i:s.u']),
        ));
    }

    #[Test]
    public function it_fails_a_cursor_without_an_expected_parameter(): void
    {
        $this->assertFalse($this->passes(
            (new PaginationCursor(['created_at' => '2026-10-25 21:00:00']))->encode(),
            new Cursor(['created_at', 'id']),
        ));
    }

    #[Test]
    public function it_fails_a_cursor_with_an_unexpected_parameter(): void
    {
        $this->assertFalse($this->passes(
            (new PaginationCursor(['created_at' => '2026-10-25 21:00:00', 'id' => 1, 'title' => 'a']))->encode(),
            new Cursor(['created_at', 'id']),
        ));
    }

    #[Test]
    public function it_accepts_the_expected_parameters_in_any_order(): void
    {
        $this->assertTrue($this->passes(
            (new PaginationCursor(['id' => 1, 'created_at' => '2026-10-25 21:00:00'], false))->encode(),
            new Cursor(['created_at', 'id']),
        ));
    }

    #[Test]
    public function it_records_the_failure_under_the_cursor_rule(): void
    {
        $validator = Validator::make(['paging' => ['after' => 'not-a-cursor']], ['paging.after' => [new Cursor]]);

        $validator->fails();

        $this->assertSame(['paging.after' => [Cursor::class => []]], $validator->failed());
    }

    private function passes(mixed $value, Cursor $rule = new Cursor): bool
    {
        return Validator::make(['paging' => ['after' => $value]], ['paging.after' => [$rule]])->passes();
    }
}
