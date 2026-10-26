<?php

declare(strict_types=1);

namespace Support\Http\Api\Request\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Pagination\Cursor as PaginationCursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use Support\Http\Api\Pagination\CursorSignature;
use Support\Http\Api\Rules\Cursor;

/**
 * @phpstan-require-extends FormRequest
 */
trait PagesByCursor
{
    /**
     * @return array<string, mixed>
     */
    public function pagingRules(): array
    {
        return self::pagingRulesFor(new Cursor);
    }

    public function size(): int
    {
        return $this->queryInput()->integer('paging.size', 10);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return CursorPaginator<int, TModel>
     */
    public function paginate(Builder $query): CursorPaginator
    {
        $fingerprint = CursorSignature::fingerprint($query);

        $validator = Validator::make($this->query->all(), self::pagingRulesFor(new Cursor($fingerprint)), $this->messages(), $this->attributes());

        if ($validator->fails()) {
            $this->failedValidation($validator);
        }

        request()->attributes->set(CursorSignature::class, $fingerprint);

        $key = $this->queryInput()->filled('paging.before') ? 'paging.before' : 'paging.after';

        return $query->cursorPaginate($this->size(), cursorName: $key, cursor: $this->cursor($key, $fingerprint));
    }

    /**
     * @return Fluent<array-key, mixed>
     */
    protected function queryInput(): Fluent
    {
        return new Fluent($this->query->all());
    }

    private function cursor(string $key, string $fingerprint): null|PaginationCursor
    {
        if (! $this->queryInput()->filled($key)) {
            return null;
        }

        $encoded = CursorSignature::verify($this->queryInput()->string($key)->toString(), $fingerprint);

        return new PaginationCursor(
            Arr::except(PaginationCursor::fromEncoded($encoded)?->toArray() ?? [], '_pointsToNextItems'),
            $key === 'paging.after',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function pagingRulesFor(Cursor $cursor): array
    {
        return [
            'paging.size' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'paging.after' => ['sometimes', $cursor],
            'paging.before' => ['sometimes', $cursor, 'prohibits:paging.after'],
        ];
    }
}
