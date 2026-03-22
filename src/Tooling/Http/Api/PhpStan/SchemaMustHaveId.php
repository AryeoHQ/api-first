<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use Support\Http\Resources\Schemas\Contracts\Schema;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Class_>
 */
#[NodeType(Class_::class)]
final class SchemaMustHaveId extends Rule
{
    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $this->inherits($node, Schema::class)
            && ! $this->hasProperty($node, 'id');
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            message: 'Schema must define a public $id property.',
            line: $node->getStartLine(),
            identifier: 'schema.id',
        );
    }

    private function hasProperty(Class_ $node, string $name): bool
    {
        foreach ($node->getProperties() as $property) {
            foreach ($property->props as $prop) {
                if ($prop->name->toString() === $name) {
                    return true;
                }
            }
        }

        return false;
    }
}
