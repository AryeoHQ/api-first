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
final class SchemaMustHaveResourceVersion extends Rule
{
    /** @var class-string */
    private string $versionClass;

    /**
     * @param  class-string|null  $versionClass
     */
    public function __construct(null|string $versionClass = null)
    {
        $this->versionClass = $versionClass ?? (string) config('api-resource-schema.version', '');
    }

    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        if ($this->versionClass === '') {
            return false;
        }

        return $this->inherits($node, Schema::class)
            && ! $this->hasTypedProperty($node, 'resourceVersion', $this->versionClass);
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            '['.class_basename(Schema::class).'] must define a public $resourceVersion property typed as ['.class_basename($this->versionClass).'].',
            $node->name?->getStartLine() ?? $node->getStartLine(),
            'schema.resourceVersion',
        );
    }

    /**
     * @param  class-string  $expectedType
     */
    private function hasTypedProperty(Class_ $node, string $name, string $expectedType): bool
    {
        foreach ($node->getProperties() as $property) {
            foreach ($property->props as $prop) {
                if ($prop->name->toString() !== $name || ! $property->isPublic()) {
                    continue;
                }

                $type = $property->type;

                if ($type === null) {
                    return false;
                }

                $typeName = $type instanceof Node\Name
                    ? $type->toString()
                    : ($type instanceof Node\Identifier ? $type->toString() : null);

                if ($typeName === null) {
                    return false;
                }

                return $typeName === $expectedType
                    || $typeName === class_basename($expectedType);
            }
        }

        return false;
    }
}
