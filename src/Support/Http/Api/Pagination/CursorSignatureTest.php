<?php

declare(strict_types=1);

namespace Support\Http\Api\Pagination;

use Illuminate\Encryption\MissingAppKeyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;
use Tests\TestCase;

#[CoversClass(CursorSignature::class)]
final class CursorSignatureTest extends TestCase
{
    private const string ENCODED = 'eyJpZCI6MSwiX3BvaW50c1RvTmV4dEl0ZW1zIjp0cnVlfQ';

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

    #[Test]
    public function it_rejects_a_cursor_from_a_list_with_the_same_order_on_another_table(): void
    {
        $signed = CursorSignature::sign(self::ENCODED, CursorSignature::fingerprint(
            (new Rfc3339ExtendedTestModel)->setTable('other_test_models')->newQuery()->orderBy('created_at')->orderBy('id'),
        ));

        $this->assertNull(CursorSignature::verify($signed, CursorSignature::fingerprint(
            Rfc3339ExtendedTestModel::query()->orderBy('created_at')->orderBy('id'),
        )));
    }

    #[Test]
    public function it_fingerprints_the_opposite_order_differently(): void
    {
        $this->assertNotSame(
            CursorSignature::fingerprint(Rfc3339ExtendedTestModel::query()->orderBy('created_at')->orderBy('id')),
            CursorSignature::fingerprint(Rfc3339ExtendedTestModel::query()->orderByDesc('created_at')->orderByDesc('id')),
        );
    }

    #[Test]
    public function it_fingerprints_an_unordered_query_by_its_key_as_cursor_pagination_orders_it(): void
    {
        $this->assertSame(
            CursorSignature::fingerprint(Rfc3339ExtendedTestModel::query()->orderBy('rfc3339_extended_test_models.id')),
            CursorSignature::fingerprint(Rfc3339ExtendedTestModel::query()),
        );
    }

    #[Test]
    public function it_fingerprints_a_union_by_its_union_order_as_cursor_pagination_orders_it(): void
    {
        $this->assertSame(
            CursorSignature::fingerprint(Rfc3339ExtendedTestModel::query()->orderBy('created_at')),
            CursorSignature::fingerprint(Rfc3339ExtendedTestModel::query()->orderBy('id')->union(Rfc3339ExtendedTestModel::query()->toBase())->orderBy('created_at')),
        );
    }

    #[Test]
    public function it_fingerprints_without_an_order_that_has_no_direction(): void
    {
        $this->assertSame(
            CursorSignature::fingerprint(Rfc3339ExtendedTestModel::query()->orderBy('created_at')),
            CursorSignature::fingerprint(Rfc3339ExtendedTestModel::query()->orderBy('created_at')->orderByRaw('random()')),
        );
    }
}
