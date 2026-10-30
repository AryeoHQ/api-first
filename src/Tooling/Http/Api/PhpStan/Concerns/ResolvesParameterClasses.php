<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan\Concerns;

use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;

trait ResolvesParameterClasses
{
    /**
     * @return list<ClassReflection>
     */
    private function parameterClasses(Param $param, Scope $scope): array
    {
        $types = match (true) {
            $param->type instanceof NullableType => [$param->type->type],
            $param->type instanceof UnionType => $param->type->types,
            default => [$param->type],
        };

        $classes = [];

        foreach ($types as $type) {
            if (! $type instanceof Name) {
                continue;
            }

            $reflection = new ObjectType($scope->resolveName($type))->getClassReflection();

            if ($reflection instanceof ClassReflection) {
                $classes[] = $reflection;
            }
        }

        return $classes;
    }
}
