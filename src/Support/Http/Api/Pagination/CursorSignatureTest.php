<?php

declare(strict_types=1);

namespace Support\Http\Api\Pagination;

use Illuminate\Encryption\MissingAppKeyException;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(CursorSignature::class)]
final class CursorSignatureTest extends TestCase
{
    private const string ENCODED = 'eyJpZCI6MSwiX3BvaW50c1RvTmV4dEl0ZW1zIjp0cnVlfQ';

    /**
     * @return array<string, array{string, string}>
     */
    public static function requestsThatDiffer(): array
    {
        return [
            'another path' => ['/owners/1/items', '/owners/2/items'],
            'another sort' => ['/items?sort=created_at', '/items?sort=-created_at'],
            'no sort' => ['/items?sort=created_at', '/items'],
            'another filter value' => ['/items?filters[status]=active', '/items?filters[status]=archived'],
            'no filters' => ['/items?filters[status]=active', '/items'],
            'filter values in another order' => ['/items?filters[status][]=active&filters[status][]=archived', '/items?filters[status][]=archived&filters[status][]=active'],
            'a filter value that is not a list' => ['/items?filters=active', '/items?filters[]=active'],
            'another search query' => ['/items?query=maple', '/items?query=oak'],
            'no search query' => ['/items?query=maple', '/items'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function requestsThatMatch(): array
    {
        return [
            'filter keys in another order' => ['/items?filters[status]=active&filters[created][to]=b&filters[created][from]=a', '/items?filters[created][from]=a&filters[created][to]=b&filters[status]=active'],
            'paging, include and other keys' => ['/items?sort=created_at', '/items?sort=created_at&paging[size]=5&paging[after]=abc&include=owner&search=maple'],
        ];
    }

    #[Test]
    public function it_verifies_a_cursor_it_signed_and_returns_the_encoded_cursor(): void
    {
        $this->assertSame(self::ENCODED, CursorSignature::verify(CursorSignature::sign(self::ENCODED, 'list'), 'list'));
    }

    #[Test]
    public function it_signs_an_encoded_cursor_with_an_unpadded_base64url_mac(): void
    {
        $this->assertMatchesRegularExpression('/\A'.self::ENCODED.'\.[A-Za-z0-9_-]{43}\z/', CursorSignature::sign(self::ENCODED, 'list'));
    }

    #[Test]
    public function it_rejects_a_cursor_signed_for_another_fingerprint(): void
    {
        $this->assertNull(CursorSignature::verify(CursorSignature::sign(self::ENCODED, 'list'), 'another list'));
    }

    #[Test]
    public function it_rejects_a_cursor_without_a_mac(): void
    {
        $this->assertNull(CursorSignature::verify(self::ENCODED, 'list'));
    }

    #[Test]
    public function it_rejects_a_cursor_signed_with_another_application_key(): void
    {
        $signed = CursorSignature::sign(self::ENCODED, 'list');

        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);

        $this->assertNull(CursorSignature::verify($signed, 'list'));
    }

    #[Test]
    public function it_decodes_a_base64_application_key(): void
    {
        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);

        $signed = CursorSignature::sign(self::ENCODED, 'list');

        config(['app.key' => str_repeat('k', 32)]);

        $this->assertSame($signed, CursorSignature::sign(self::ENCODED, 'list'));
    }

    #[Test]
    public function it_refuses_to_sign_without_an_application_key(): void
    {
        config(['app.key' => '']);

        $this->expectException(MissingAppKeyException::class);

        CursorSignature::sign(self::ENCODED, 'list');
    }

    #[DataProvider('requestsThatDiffer')]
    #[Test]
    public function it_fingerprints_a_request_with_another_path_sort_filters_or_query_differently(string $issued, string $sent): void
    {
        $this->assertNotSame(CursorSignature::fingerprint(Request::create($issued)), CursorSignature::fingerprint(Request::create($sent)));
    }

    #[DataProvider('requestsThatMatch')]
    #[Test]
    public function it_fingerprints_a_request_by_its_path_sort_filters_and_query_alone(string $issued, string $sent): void
    {
        $this->assertSame(CursorSignature::fingerprint(Request::create($issued)), CursorSignature::fingerprint(Request::create($sent)));
    }
}
