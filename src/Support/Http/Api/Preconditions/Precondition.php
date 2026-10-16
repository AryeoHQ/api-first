<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Support\Http\Api\Errors\Exceptions\PreconditionFailed;
use Support\Http\Api\Errors\Exceptions\PreconditionRequired;
use Support\Http\Api\Preconditions\Contracts\Target;

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
        return DB::transaction(function () use ($write): mixed {
            $target = $this->target();

            $target->lock();

            $this->evaluate($target);

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

        $tag = $target->tag();

        throw_unless($tag->matches(implode(', ', $this->headers->all('If-Match'))), PreconditionFailed::class, (string) $tag);
    }
}
