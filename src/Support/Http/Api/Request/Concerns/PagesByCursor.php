<?php

declare(strict_types=1);

namespace Support\Http\Api\Request\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Pagination\Cursor as PaginationCursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
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
        return [
            'paging.size' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'paging.after' => ['sometimes', 'string', new Cursor],
            'paging.before' => ['sometimes', 'string', new Cursor, 'prohibits:paging.after'],
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    public function validationData(): array
    {
        return $this->query->all();
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
        $key = $this->queryInput()->filled('paging.before') ? 'paging.before' : 'paging.after';

        return $query->cursorPaginate($this->size(), cursorName: $key, cursor: $this->cursor($key, $query));
    }

    /**
     * @return Fluent<array-key, mixed>
     */
    protected function queryInput(): Fluent
    {
        return new Fluent($this->query->all());
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    private function cursor(string $key, Builder $query): null|PaginationCursor
    {
        if (! $this->queryInput()->filled($key)) {
            return null;
        }

        $encoded = $this->queryInput()->get($key);

        $validator = Validator::make(
            Arr::undot([$key => $encoded]),
            [$key => [new Cursor(self::cursorParameters($query), [$query->getModel()->getDateFormat()])]],
            $this->messages(),
            $this->attributes(),
        );

        if ($validator->fails()) {
            $this->failedValidation($validator);
        }

        return new PaginationCursor(
            Arr::except(PaginationCursor::fromEncoded($encoded)?->toArray() ?? [], '_pointsToNextItems'),
            $key === 'paging.after',
        );
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return list<string>
     */
    private static function cursorParameters(Builder $query): array
    {
        return collect($query->getQuery()->unionOrders)
            ->whenEmpty(fn (): Collection => collect($query->getQuery()->orders))
            ->whenEmpty(fn (): Collection => collect([['column' => $query->getModel()->getQualifiedKeyName(), 'direction' => 'asc']]))
            ->filter(fn (array $order): bool => Arr::has($order, 'direction'))
            ->map(fn (array $order): string => (string) $order['column'])
            ->values()
            ->all();
    }
}
