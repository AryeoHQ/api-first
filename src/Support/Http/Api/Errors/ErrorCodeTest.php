<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(ErrorCode::class)]
final class ErrorCodeTest extends TestCase
{
    #[Test]
    public function it_lists_only_codes_that_the_catalog_holds(): void
    {
        $catalog = require __DIR__.'/../../../../../resources/error-codes.php';

        foreach (ErrorCode::cases() as $code) {
            $this->assertContains($code->value, $catalog, $code->name);
        }
    }
}
