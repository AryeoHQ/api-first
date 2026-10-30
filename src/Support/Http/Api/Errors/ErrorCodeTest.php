<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

#[CoversClass(ErrorCode::class)]
final class ErrorCodeTest extends TestCase
{
    #[Test]
    public function it_holds_only_the_four_categories(): void
    {
        $this->assertSame(
            ['error', 'authentication', 'authorization', 'validation'],
            array_map(fn (ErrorCode $code): string => $code->value, ErrorCode::cases()),
        );
    }

    #[Test]
    #[TestWith([401, 'authentication'])]
    #[TestWith([403, 'authorization'])]
    #[TestWith([422, 'validation'])]
    #[TestWith([400, 'error'])]
    #[TestWith([404, 'error'])]
    #[TestWith([405, 'error'])]
    #[TestWith([409, 'error'])]
    #[TestWith([412, 'error'])]
    #[TestWith([415, 'error'])]
    #[TestWith([428, 'error'])]
    #[TestWith([429, 'error'])]
    #[TestWith([500, 'error'])]
    #[TestWith([503, 'error'])]
    public function it_names_the_category_of_a_status(int $status, string $code): void
    {
        $this->assertSame($code, ErrorCode::fromStatus($status)->value);
    }
}
