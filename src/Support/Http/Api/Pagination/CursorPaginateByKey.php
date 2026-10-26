<?php

declare(strict_types=1);

namespace Support\Http\Api\Pagination;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\Cursor as PaginationCursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use Support\Http\Api\Rules\Cursor;

final class CursorPaginateByKey
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(Cursor $cursor): array
    {
        return [
            'paging.size' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'paging.after' => ['sometimes', $cursor],
            'paging.before' => ['sometimes', $cursor, 'prohibits:paging.after'],
        ];
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>|Relation<TModel, Model, mixed>  $query
     * @return CursorPaginator<int, TModel>
     */
    public function __invoke(Builder|Relation $query, Request $request): CursorPaginator
    {
        $fingerprint = CursorSignature::fingerprint($query instanceof Relation ? $query->getQuery() : $query);

        Validator::make($request->query->all(), self::rules(new Cursor($fingerprint)))->validate();

        $request->attributes->set(CursorSignature::class, $fingerprint);

        $paging = new Fluent($request->query->all());
        $key = $paging->filled('paging.before') ? 'paging.before' : 'paging.after';

        return $query->cursorPaginate($paging->integer('paging.size', 10), cursorName: $key, cursor: self::cursor($paging, $key, $fingerprint));
    }

    /**
     * @param  Fluent<array-key, mixed>  $paging
     */
    private static function cursor(Fluent $paging, string $key, string $fingerprint): null|PaginationCursor
    {
        if (! $paging->filled($key)) {
            return null;
        }

        $encoded = CursorSignature::verify($paging->string($key)->toString(), $fingerprint);

        return new PaginationCursor(
            Arr::except(PaginationCursor::fromEncoded($encoded)?->toArray() ?? [], '_pointsToNextItems'),
            $key === 'paging.after',
        );
    }
}
