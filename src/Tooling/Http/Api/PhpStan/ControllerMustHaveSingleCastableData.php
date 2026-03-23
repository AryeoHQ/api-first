<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use Illuminate\Support\Collection;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use Support\Http\Requests\Contracts\CastableData;
use Tooling\PhpStan\Rules\Rule;
use Tooling\Rules\Attributes\NodeType;

/**
 * @extends Rule<Class_>
 */
#[NodeType(Class_::class)]
final class ControllerMustHaveSingleCastableData extends Rule
{
    use Concerns\ValidatesController;

    /** @var Collection<int, Node\Param> */
    private Collection $castableParams;

    /**
     * @param  Class_  $node
     */
    public function prepare(Node $node, Scope $scope): void
    {
        $this->castableParams = collect($node->getMethod('__invoke')?->getParams())
            ->filter(fn (Node\Param $param) => $param->type instanceof Node\Name)
            ->filter(fn (Node\Param $param) => (new ObjectType($scope->resolveName($param->type)))->getClassReflection() !== null)
            ->filter(fn (Node\Param $param) => $this->inherits(
                (new ObjectType($scope->resolveName($param->type)))->getClassReflection(),
                CastableData::class,
            ));
    }

    /**
     * @param  Class_  $node
     */
    public function shouldHandle(Node $node, Scope $scope): bool
    {
        return $this->isController($node, $scope)
            && $this->castableParams->count() > 1;
    }

    /**
     * @param  Class_  $node
     */
    public function handle(Node $node, Scope $scope): void
    {
        $this->error(
            message: 'Controllers must not have more than one CastableData parameter.',
            line: $node->getMethod('__invoke')->getStartLine(),
            identifier: 'controller.parameters.castableData',
        );
    }
}
