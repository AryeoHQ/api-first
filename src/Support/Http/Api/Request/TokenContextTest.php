<?php

declare(strict_types=1);

namespace Support\Http\Api\Request;

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Http\Authorizer;
use Support\Http\Validator;
use Tests\TestCase;

#[CoversClass(TokenContext::class)]
final class TokenContextTest extends TestCase
{
    #[Test]
    public function authorizer_has_actor_macro(): void
    {
        $this->assertTrue(Authorizer::hasMacro('actor'));
    }

    #[Test]
    public function authorizer_has_subject_macro(): void
    {
        $this->assertTrue(Authorizer::hasMacro('subject'));
    }

    #[Test]
    public function validator_has_actor_macro(): void
    {
        $this->assertTrue(Validator::hasMacro('actor'));
    }

    #[Test]
    public function validator_has_subject_macro(): void
    {
        $this->assertTrue(Validator::hasMacro('subject'));
    }

    #[Test]
    public function actor_returns_null_when_unauthenticated(): void
    {
        $request = $this->createAuthorizer();

        $this->assertNull($request->actor());
    }

    #[Test]
    public function subject_returns_null_when_unauthenticated(): void
    {
        $request = $this->createAuthorizer();

        $this->assertNull($request->subject());
    }

    #[Test]
    public function actor_returns_the_authenticated_user(): void
    {
        $user = new User;
        $request = $this->createAuthorizer();
        $request->setUserResolver(fn () => $user);

        $this->assertSame($user, $request->actor());
    }

    #[Test]
    public function subject_returns_the_authenticated_user(): void
    {
        $user = new User;
        $request = $this->createAuthorizer();
        $request->setUserResolver(fn () => $user);

        $this->assertSame($user, $request->subject());
    }

    private function createAuthorizer(): Authorizer
    {
        return new class extends Authorizer
        {
            public function authorize(): bool
            {
                return true;
            }
        };
    }
}
