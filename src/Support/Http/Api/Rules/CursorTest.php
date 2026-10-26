<?php

declare(strict_types=1);

namespace Support\Http\Api\Rules;

use Illuminate\Http\Request;
use Illuminate\Pagination\Cursor as PaginationCursor;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Support\Http\Api\Pagination\CursorSignature;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;
use Tests\TestCase;

#[CoversClass(Cursor::class)]
final class CursorTest extends TestCase
{
    private const string MAC = 'bWFj';

    /**
     * @return array<string, array{mixed}>
     */
    public static function valuesThatAreNotCursors(): array
    {
        return [
            'an undecodable string' => ['not-a-cursor'],
            'a cursor without a mac' => [(new PaginationCursor(['id' => 1]))->encode()],
            'a cursor with a character outside base64url' => [self::withMac((new PaginationCursor(['id' => 1]))->encode().'!')],
            'a cursor with two macs' => [self::withMac(self::withMac((new PaginationCursor(['id' => 1]))->encode()))],
            'a cursor that is not strict base64' => [self::withMac((new PaginationCursor(['id' => 123]))->encode().'A')],
            'an encoded scalar' => [self::withMac(rtrim(base64_encode('1'), '='))],
            'an encoded object without a direction' => [self::withMac(rtrim(base64_encode('{"created_at":"2026-10-25 21:00:00"}'), '='))],
            'a list' => [['not-a-cursor']],
            'a parameter that is not a scalar' => [self::withMac((new PaginationCursor(['created_at' => ['2026-10-25 21:00:00']]))->encode())],
            'a null parameter' => [self::withMac((new PaginationCursor(['created_at' => null]))->encode())],
            'a parameter with a NUL byte' => [self::withMac((new PaginationCursor(['created_at' => "2026-10-25 21:00:00\0"]))->encode())],
            'a NUL byte in the encoded JSON' => [self::withMac(rtrim(strtr(base64_encode("{\"created_at\":\"2026-10-25 21:00:00\0\",\"_pointsToNextItems\":true}"), '+/', '-_'), '='))],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function stringsThatReadAsDates(): array
    {
        return [
            'seconds' => ['2026-10-25 21:00:00'],
            'milliseconds' => ['2026-10-25T21:00:00.100+00:00'],
            'microseconds' => ['2026-10-25T21:00:00.123456+00:00'],
            'milliseconds with a Z offset' => ['2026-10-25T21:00:00.100Z'],
            'a seconds date that does not exist' => ['2026-02-31 00:00:00'],
            'a milliseconds date that does not exist' => ['2026-02-31T00:00:00.000+00:00'],
            'a microseconds date that does not exist' => ['2026-02-31T00:00:00.000000+00:00'],
            'an hour past midnight' => ['2026-10-25 24:00:00'],
            'a title that starts with a date that does not exist' => ['2026-02-31 launch notes'],
        ];
    }

    #[Test]
    public function it_accepts_a_cursor_that_cursor_pagination_issued_and_this_list_signed(): void
    {
        Rfc3339ExtendedTestModel::factory()->create(['created_at' => '2026-10-25T21:00:00.100+00:00']);
        Rfc3339ExtendedTestModel::factory()->create(['created_at' => '2026-10-25T21:00:00.400+00:00']);

        $this->app->instance('request', Request::create('/test', 'GET'));

        $query = Rfc3339ExtendedTestModel::query()->orderBy('created_at')->orderBy('id');
        $fingerprint = CursorSignature::fingerprint($query);
        $cursor = CursorSignature::sign((string) $query->cursorPaginate(1)->nextCursor()?->encode(), $fingerprint);

        $this->assertTrue($this->passes($cursor, new Cursor($fingerprint)));
    }

    #[Test]
    public function it_fails_a_cursor_signed_for_another_list(): void
    {
        $cursor = CursorSignature::sign((new PaginationCursor(['id' => 1]))->encode(), 'another list');

        $this->assertFalse($this->passes($cursor, new Cursor('list')));
    }

    #[Test]
    public function it_fails_a_cursor_with_a_changed_payload(): void
    {
        $mac = Str::afterLast(CursorSignature::sign((new PaginationCursor(['id' => 1]))->encode(), 'list'), '.');

        $this->assertFalse($this->passes((new PaginationCursor(['id' => 2]))->encode().'.'.$mac, new Cursor('list')));
    }

    #[Test]
    public function it_fails_a_cursor_longer_than_1024_bytes(): void
    {
        $cursor = CursorSignature::sign((new PaginationCursor(['id' => str_repeat('a', 701)]))->encode(), 'list');

        $this->assertSame([1026, false, false], [strlen($cursor), $this->passes($cursor), $this->passes($cursor, new Cursor('list'))]);
    }

    #[Test]
    public function it_accepts_a_cursor_of_1024_bytes(): void
    {
        $cursor = CursorSignature::sign((new PaginationCursor(['id' => str_repeat('a', 700)]))->encode(), 'list');

        $this->assertSame([1024, true], [strlen($cursor), $this->passes($cursor, new Cursor('list'))]);
    }

    #[DataProvider('valuesThatAreNotCursors')]
    #[Test]
    public function it_fails_a_value_that_is_not_a_cursor(mixed $value): void
    {
        $this->assertFalse($this->passes($value));
    }

    #[DataProvider('stringsThatReadAsDates')]
    #[Test]
    public function it_accepts_a_string_parameter_whether_or_not_it_reads_as_a_date_that_exists(string $value): void
    {
        $this->assertTrue($this->passes(self::withMac((new PaginationCursor(['created_at' => $value]))->encode())));
    }

    #[Test]
    public function it_records_the_failure_under_the_cursor_rule(): void
    {
        $validator = Validator::make(['paging' => ['after' => 'not-a-cursor']], ['paging.after' => [new Cursor]]);

        $validator->fails();

        $this->assertSame(['paging.after' => [Cursor::class => []]], $validator->failed());
    }

    private static function withMac(string $encoded): string
    {
        return $encoded.'.'.self::MAC;
    }

    private function passes(mixed $value, Cursor $rule = new Cursor): bool
    {
        return Validator::make(['paging' => ['after' => $value]], ['paging.after' => [$rule]])->passes();
    }
}
