<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles\Store;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(Validator::class)]
final class ValidatorTest extends TestCase
{
    #[Test]
    public function it_passes(): void
    {
        Validator::factory()->make()->validateResolved();

        $this->assertTrue(true);
    }

    #[Test]
    public function it_fails_without_title(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        Validator::factory()->withoutTitle()->make()->validateResolved();
    }

    #[Test]
    public function it_fails_without_body(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        Validator::factory()->withoutBody()->make()->validateResolved();
    }
}
