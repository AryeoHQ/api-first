<?php

declare(strict_types=1);

namespace Support\Http\Api\Pagination;

use Illuminate\Pagination\Cursor as PaginationCursor;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use Support\Http\Api\Rules\Cursor;
use Support\Http\Api\Rules\PageSize;
use Support\Http\Api\Rules\ProhibitsParameter;
use Support\Http\Api\Rules\UnknownParameter;

final class CursorResolver
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(Cursor $cursor): array
    {
        $rules = [
            'paging.size' => ['sometimes', new PageSize],
            'paging.after' => ['sometimes', $cursor],
            'paging.before' => ['sometimes', $cursor, new ProhibitsParameter('paging.after')],
        ];

        return [
            ...$rules,
            'paging' => ['sometimes', Rule::when(fn (Fluent $query): bool => ! is_array($query->get('paging')), [new UnknownParameter])],
            'paging.*' => Rule::forEach(fn (mixed $value, string $attribute): array => array_key_exists($attribute, $rules) ? [] : [new UnknownParameter]),
        ];
    }

    public function __invoke(string $cursorName = 'cursor'): null|PaginationCursor
    {
        $request = request();
        $fingerprint = CursorSignature::fingerprint($request);

        Validator::make($request->query->all(), self::rules(new Cursor($fingerprint)))->validate();

        $request->attributes->set(CursorSignature::class, $fingerprint);

        $paging = new Fluent($request->query->all());
        $key = $paging->filled('paging.before') ? 'paging.before' : 'paging.after';

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
