<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Routing\Enums\Method;
use Tests\TestCase;

#[CoversClass(Endpoint::class)]
final class EndpointTest extends TestCase
{
    #[Test]
    public function it_resolves_the_http_method(): void
    {
        foreach (Endpoint::cases() as $endpoint) {
            $this->assertInstanceOf(Method::class, $endpoint->method());
        }
    }

    #[Test]
    public function it_resolves_the_scope(): void
    {
        foreach (Endpoint::cases() as $endpoint) {
            $this->assertInstanceOf(Scope::class, $endpoint->scope());
        }
    }
}
