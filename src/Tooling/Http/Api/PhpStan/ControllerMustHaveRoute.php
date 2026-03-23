<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use Support\Routing\Attributes\Route;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Class_>
 */
#[NodeType(Class_::class)]
final class ControllerMustHaveRoute extends Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return str_contains($node->name?->toString() ?? '', 'Controller')
            && $node->getMethod('__invoke') !== null
            && ! $this->hasAttribute($node->getMethod('__invoke'), Route::class);
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            message: 'Controllers define their endpoints with the Route attribute.',
            line: $node->getStartLine(),
            identifier: 'controller.attributes.route',
        );
    }
}
