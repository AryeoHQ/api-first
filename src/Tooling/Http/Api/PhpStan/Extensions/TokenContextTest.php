<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan\Extensions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Tooling\PhpStan\Reflection\Methods\Macro;

#[CoversClass(TokenContext::class)]
final class TokenContextTest extends PHPStanTestCase
{
    private TokenContext $extension;

    protected function setUp(): void
    {
        $this->extension = new TokenContext(self::createReflectionProvider());
    }

    #[Test]
    public function it_has_actor_on_request(): void
    {
        $classReflection = self::createReflectionProvider()->getClass(Request::class);

        $this->assertTrue($this->extension->hasMethod($classReflection, 'actor'));
    }

    #[Test]
    public function it_has_subject_on_request(): void
    {
        $classReflection = self::createReflectionProvider()->getClass(Request::class);

        $this->assertTrue($this->extension->hasMethod($classReflection, 'subject'));
    }

    #[Test]
    public function it_has_actor_on_form_request(): void
    {
        $classReflection = self::createReflectionProvider()->getClass(FormRequest::class);

        $this->assertTrue($this->extension->hasMethod($classReflection, 'actor'));
    }

    #[Test]
    public function it_does_not_have_method_on_unrelated_class(): void
    {
        $classReflection = self::createReflectionProvider()->getClass(stdClass::class);

        $this->assertFalse($this->extension->hasMethod($classReflection, 'actor'));
    }

    #[Test]
    public function it_does_not_have_nonexistent_method(): void
    {
        $classReflection = self::createReflectionProvider()->getClass(Request::class);

        $this->assertFalse($this->extension->hasMethod($classReflection, 'nonExistentMethod'));
    }

    #[Test]
    public function it_returns_an_instance_macro_for_request(): void
    {
        $classReflection = self::createReflectionProvider()->getClass(Request::class);

        $method = $this->extension->getMethod($classReflection, 'actor');

        $this->assertInstanceOf(Macro::class, $method);
        $this->assertSame('actor', $method->getName());
        $this->assertFalse($method->isStatic());
    }
}
