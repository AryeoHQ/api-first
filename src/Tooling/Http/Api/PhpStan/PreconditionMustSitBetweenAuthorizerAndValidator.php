<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use Support\Http\Api\Preconditions\Precondition;
use Support\Http\Authorizer;
use Support\Http\Validator;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<ClassMethod>
 */
#[NodeType(ClassMethod::class)]
final class PreconditionMustSitBetweenAuthorizerAndValidator extends Rule
{
    /**
     * @param  ClassMethod  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $node->name->toString() === '__invoke'
            && $this->misplacedPrecondition($node, $scope) instanceof Param;
    }

    /**
     * @param  ClassMethod  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            message: 'Precondition parameters must be declared after authorizer parameters and before validator parameters.',
            line: $this->misplacedPrecondition($node, $scope)?->getStartLine() ?? $node->getStartLine(),
            identifier: 'controller.precondition.order',
        );
    }

    private function misplacedPrecondition(ClassMethod $method, Scope $scope): null|Param
    {
        $kinds = array_map(fn (Param $param): null|string => $this->kind($param, $scope), $method->params);
        $authorizers = array_keys($kinds, Authorizer::class, true);
        $validators = array_keys($kinds, Validator::class, true);
        $lastAuthorizer = $authorizers === [] ? -1 : max($authorizers);
        $firstValidator = $validators === [] ? PHP_INT_MAX : min($validators);

        foreach ($method->params as $index => $param) {
            if ($kinds[$index] === Precondition::class && ($index < $lastAuthorizer || $index > $firstValidator)) {
                return $param;
            }
        }

        return null;
    }

    private function kind(Param $param, Scope $scope): null|string
    {
        $type = match (true) {
            $param->type instanceof NullableType => $param->type->type,
            $param->type instanceof UnionType => collect($param->type->types)->first(fn (Node $member): bool => $member instanceof Name),
            default => $param->type,
        };

        if (! $type instanceof Name) {
            return null;
        }

        $reflection = new ObjectType($scope->resolveName($type))->getClassReflection();

        if (! $reflection instanceof ClassReflection) {
            return null;
        }

        foreach ([Authorizer::class, Precondition::class, Validator::class] as $kind) {
            if ($reflection->getName() === $kind || $this->inherits($reflection, $kind)) {
                return $kind;
            }
        }

        return null;
    }
}
