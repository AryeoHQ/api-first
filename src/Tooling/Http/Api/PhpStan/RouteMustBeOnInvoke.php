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
final class RouteMustBeOnInvoke extends Rule
{
    use Concerns\ValidatesController;

    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $this->isController($node, $scope);
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        foreach ($node->getMethods() as $method) {
            if ($method->name->toString() === '__invoke') {
                continue;
            }

            if ($this->hasAttribute($method, Route::class)) {
                $this->error(
                    message: "The #[Route] attribute must only be applied to __invoke(), not {$method->name->toString()}().",
                    line: $method->getStartLine(),
                    identifier: 'controller.attributes.route.onlyInvoke',
                );
            }
        }
    }
}
