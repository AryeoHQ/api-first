<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use Support\Http\Api\Preconditions\Precondition;
use Support\Http\Authorizer;
use Support\Http\Validator;
use Tooling\Http\Api\PhpStan\Concerns\ResolvesParameterClasses;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<ClassMethod>
 */
#[NodeType(ClassMethod::class)]
final class PreconditionMustSitBetweenAuthorizerAndValidator extends Rule
{
    use ResolvesParameterClasses;

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
        foreach ($this->parameterClasses($param, $scope) as $reflection) {
            foreach ([Authorizer::class, Precondition::class, Validator::class] as $kind) {
                if ($reflection->getName() === $kind || $this->inherits($reflection, $kind)) {
                    return $kind;
                }
            }
        }

        return null;
    }
}
