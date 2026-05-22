<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use Support\Events\Log\Contracts\Loggable;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Class_>
 */
#[NodeType(Class_::class)]
final class ModelMustImplementLoggable extends Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $this->inherits($node, Model::class)
            && $this->doesNotInherit($node, Loggable::class);
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            '['.class_basename(Model::class).'] must implement the ['.class_basename(Loggable::class).'] contract.',
            $node->name?->getStartLine() ?? $node->getStartLine(),
            'Model.Loggable.required',
        );
    }
}
