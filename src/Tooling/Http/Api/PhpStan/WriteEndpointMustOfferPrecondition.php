<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\ObjectType;
use Support\Http\Api\Preconditions\Precondition;
use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<ClassMethod>
 */
#[NodeType(ClassMethod::class)]
final class WriteEndpointMustOfferPrecondition extends Rule
{
    /**
     * @param  ClassMethod  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        $route = $this->route($node);

        return $node->name->toString() === '__invoke'
            && $route instanceof Attribute
            && $this->writesToBoundModel($route, $scope)
            && ! $this->takesPrecondition($node, $scope);
    }

    /**
     * @param  ClassMethod  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            message: 'A PUT, PATCH, or DELETE endpoint, or a POST action on a bound model, must take a Precondition parameter.',
            line: $node->name->getStartLine(),
            identifier: 'controller.precondition.missing',
        );
    }

    private function route(ClassMethod $method): null|Attribute
    {
        foreach ($method->attrGroups as $group) {
            foreach ($group->attrs as $attribute) {
                if ($attribute->name->toString() === Route::class) {
                    return $attribute;
                }
            }
        }

        return null;
    }

    private function writesToBoundModel(Attribute $route, Scope $scope): bool
    {
        $methods = $this->methods($route, $scope);

        if (array_intersect($methods, [Method::Put->name, Method::Patch->name, Method::Delete->name]) !== []) {
            return true;
        }

        return in_array(Method::Post->name, $methods, true) && $this->isInstanceAction($route, $scope);
    }

    /**
     * @return list<string>
     */
    private function methods(Attribute $route, Scope $scope): array
    {
        $argument = $this->argument($route, 'methods', 2);

        if (! $argument instanceof Expr) {
            return [];
        }

        $type = $scope->getType($argument);
        $cases = $type->isArray()->yes() ? $type->getIterableValueType()->getEnumCases() : $type->getEnumCases();

        return array_map(fn (EnumCaseObjectType $case): string => $case->getEnumCaseName(), $cases);
    }

    private function isInstanceAction(Attribute $route, Scope $scope): bool
    {
        $argument = $this->argument($route, 'uri', 1);

        if (! $argument instanceof Expr) {
            return false;
        }

        return array_any(
            $scope->getType($argument)->getConstantStrings(),
            fn (ConstantStringType $uri): bool => preg_match('#\{[^}]+\}/actions/#', $uri->getValue()) === 1,
        );
    }

    private function argument(Attribute $attribute, string $name, int $position): null|Expr
    {
        foreach ($attribute->args as $index => $argument) {
            if ($argument->name?->toString() === $name || ($argument->name === null && $index === $position)) {
                return $argument->value;
            }
        }

        return null;
    }

    private function takesPrecondition(ClassMethod $method, Scope $scope): bool
    {
        return array_any($method->params, fn (Param $param): bool => $this->isPrecondition($param, $scope));
    }

    private function isPrecondition(Param $param, Scope $scope): bool
    {
        $types = match (true) {
            $param->type instanceof NullableType => [$param->type->type],
            $param->type instanceof UnionType => $param->type->types,
            default => [$param->type],
        };

        foreach ($types as $type) {
            if (! $type instanceof Name) {
                continue;
            }

            $reflection = new ObjectType($scope->resolveName($type))->getClassReflection();

            if ($reflection instanceof ClassReflection && $this->inherits($reflection, Precondition::class)) {
                return true;
            }
        }

        return false;
    }
}
