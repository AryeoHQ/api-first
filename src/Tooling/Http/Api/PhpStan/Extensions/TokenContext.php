<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan\Extensions;

use Illuminate\Http\Request;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use Support\Http\Api\Request as Requests;
use Tooling\PhpStan\Reflection\Classes\Mixin;
use Tooling\PhpStan\Reflection\Methods\Macro;

class TokenContext implements MethodsClassReflectionExtension
{
    private Mixin $mixin;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->mixin = new Mixin($reflectionProvider, Requests\TokenContext::class);
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! $classReflection->is(Request::class)) {
            return false;
        }

        return $this->mixin->hasMethod($classReflection, $methodName);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $method = $this->mixin->getMethod($classReflection, $methodName);

        if (! $method instanceof Macro) {
            throw new ShouldNotHappenException;
        }

        return $method;
    }
}
