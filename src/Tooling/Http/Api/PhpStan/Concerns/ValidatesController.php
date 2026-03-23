<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan\Concerns;

use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;

trait ValidatesController
{
    protected function isController(Class_ $node, Scope $scope): bool
    {
        return $node->name?->toString() === 'Controller'
            && str_contains($scope->getNamespace() ?? '', 'Http\\Api')
            && $this->hasMethod($node, '__invoke');
    }
}
