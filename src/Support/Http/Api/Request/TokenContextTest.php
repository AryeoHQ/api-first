<?php

declare(strict_types=1);

namespace Support\Http\Api\Request;

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(TokenContext::class)]
final class TokenContextTest extends TestCase
{
    #[Test]
    public function it_returns_null_actor_when_unauthenticated(): void
    {
        $request = Request::create('/test');

        $this->assertNull($request->actor());
    }

    #[Test]
    public function it_returns_null_subject_when_unauthenticated(): void
    {
        $request = Request::create('/test');

        $this->assertNull($request->subject());
    }

    #[Test]
    public function it_returns_the_authenticated_user_as_actor(): void
    {
        $request = Request::create('/test');
        $user = new User;
        $request->setUserResolver(fn () => $user);

        $this->assertSame($user, $request->actor());
    }

    #[Test]
    public function it_returns_the_authenticated_user_as_subject(): void
    {
        $request = Request::create('/test');
        $user = new User;
        $request->setUserResolver(fn () => $user);

        $this->assertSame($user, $request->subject());
    }
}
