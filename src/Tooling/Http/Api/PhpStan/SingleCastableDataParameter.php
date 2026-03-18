<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use Support\Http\Requests\Contracts\CastableData;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Class_>
 */
#[NodeType(Class_::class)]
final class SingleCastableDataParameter extends Rule
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return str_contains($node->name?->toString() ?? '', 'Controller')
            && $node->getMethod('__invoke') !== null;
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $params = $node->getMethod('__invoke')->getParams();

        $count = 0;

        foreach ($params as $param) {
            if (! $param->type instanceof Node\Name) {
                continue;
            }

            $className = $scope->resolveName($param->type);

            if (! $this->reflectionProvider->hasClass($className)) {
                continue;
            }

            $classReflection = $this->reflectionProvider->getClass($className);

            if ($classReflection->implementsInterface(CastableData::class)) {
                $count++;
            }
        }

        if ($count > 1) {
            $this->error(
                message: 'Controllers must not have more than one CastableData parameter.',
                line: $node->getMethod('__invoke')->getStartLine(),
                identifier: 'controller.parameters.castableData',
            );
        }
    }
}
