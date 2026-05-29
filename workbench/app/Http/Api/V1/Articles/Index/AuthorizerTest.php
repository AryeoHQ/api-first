<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles\Index;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(Authorizer::class)]
final class AuthorizerTest extends TestCase
{
    #[Test]
    public function it_works(): void
    {
        $this->assertTrue(true);
    }
}
