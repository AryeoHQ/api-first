<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\UnionType;
use PhpParser\NodeFinder;
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
        return $node->name->toString() === '__invoke'
            && ($this->lacksPrecondition($node, $scope) || $this->unguardedPreconditions($node, $scope) !== []);
    }

    /**
     * @param  ClassMethod  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        if ($this->lacksPrecondition($node, $scope)) {
            $this->error(
                message: 'A PUT, PATCH, or DELETE endpoint, or a POST action on a bound model, must take a Precondition parameter.',
                line: $node->name->getStartLine(),
                identifier: 'controller.precondition.missing',
            );
        }

        foreach ($this->unguardedPreconditions($node, $scope) as $param) {
            $this->error(
                message: 'A controller that takes a Precondition must call guard() on it in __invoke().',
                line: $node->name->getStartLine(),
                identifier: 'controller.precondition.unguarded',
            );
        }
    }

    private function lacksPrecondition(ClassMethod $method, Scope $scope): bool
    {
        $route = $this->route($method);

        return $route instanceof Attribute
            && $this->writesToBoundModel($route, $scope)
            && ! $this->takesRequiredPrecondition($method, $scope);
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

        if (in_array(Method::Any->name, $methods, true)) {
            return $this->uriMatches($route, $scope, '#\{[^}]+\}#');
        }

        return in_array(Method::Post->name, $methods, true) && $this->uriMatches($route, $scope, '#\{[^}]+\}/actions/#');
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

    private function uriMatches(Attribute $route, Scope $scope, string $pattern): bool
    {
        $argument = $this->argument($route, 'uri', 1);

        if (! $argument instanceof Expr) {
            return false;
        }

        return array_any(
            $scope->getType($argument)->getConstantStrings(),
            fn (ConstantStringType $uri): bool => preg_match($pattern, $uri->getValue()) === 1,
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

    private function takesRequiredPrecondition(ClassMethod $method, Scope $scope): bool
    {
        return array_any(
            $method->params,
            fn (Param $param): bool => $param->type instanceof Name && $param->default === null && ! $param->variadic && $this->isPrecondition($param, $scope),
        );
    }

    /**
     * @return list<Param>
     */
    private function unguardedPreconditions(ClassMethod $method, Scope $scope): array
    {
        return array_values(array_filter(
            $method->params,
            fn (Param $param): bool => $this->isPrecondition($param, $scope) && ! $this->guards($method, $param),
        ));
    }

    private function guards(ClassMethod $method, Param $param): bool
    {
        return new NodeFinder()->findFirst($method->stmts ?? [], fn (Node $node): bool => ($node instanceof MethodCall || $node instanceof NullsafeMethodCall)
            && $node->name instanceof Identifier
            && $node->name->toString() === 'guard'
            && $node->var instanceof Variable
            && $param->var instanceof Variable
            && $node->var->name === $param->var->name) !== null;
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
