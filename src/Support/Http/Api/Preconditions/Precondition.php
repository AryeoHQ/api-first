<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Support\Http\Api\Errors\Exceptions\PreconditionFailed;
use Support\Http\Api\Errors\Exceptions\PreconditionRequired;
use Support\Http\Api\Preconditions\Contracts\Target;
use Support\Http\Api\Preconditions\Exceptions\ParentsChanged;
use Support\Http\Resources\Schemas\Tags\EntityTagList;

abstract class Precondition extends FormRequest
{
    protected bool $required = false;

    abstract public function target(): Target;

    final public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    final public function rules(): array
    {
        return [];
    }

    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $write
     * @return TResult
     */
    final public function guard(Closure $write): mixed
    {
        $target = $this->target();

        return $target->connection()->transaction(function () use ($target, $write): mixed {
            $moved = null;

            try {
                $target->lock();
            } catch (ParentsChanged $exception) {
                $moved = $exception;
            }

            $this->evaluate($target);

            if ($moved !== null) {
                throw $moved;
            }

            return $write();
        });
    }

    final protected function passedValidation(): void
    {
        $this->evaluate($this->target());
    }

    private function evaluate(Target $target): void
    {
        if (! $this->headers->has('If-Match')) {
            throw_if($this->required, PreconditionRequired::class);

            return;
        }

        $compared = $target->compared();

        throw_unless(EntityTagList::parse($this->headers->all('If-Match'))?->matchesRevisions($target->apiVersion(), $compared) ?? false, PreconditionFailed::class);
    }
}
