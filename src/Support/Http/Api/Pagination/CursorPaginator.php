<?php

declare(strict_types=1);

namespace Support\Http\Api\Pagination;

use DateTimeInterface;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator as UpstreamCursorPaginator;
use Illuminate\Support\Arr;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends UpstreamCursorPaginator<TKey, TValue>
 */
final class CursorPaginator extends UpstreamCursorPaginator
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'next_cursor' => self::encode($this->nextCursor()),
            'prev_cursor' => self::encode($this->previousCursor()),
        ];
    }

    /**
     * @param  Cursor|null  $cursor
     */
    #[\Override]
    public function url($cursor): string
    {
        $parameters = $cursor === null ? [] : [$this->cursorName => self::encode($cursor)];

        return $this->path()
            .(str_contains((string) $this->path(), '?') ? '&' : '?')
            .Arr::query([...$this->query, ...$parameters])
            .$this->buildFragment();
    }

    #[\Override]
    protected function ensureParameterIsPrimitive(mixed $parameter): mixed
    {
        return $parameter instanceof DateTimeInterface
            ? $parameter->format(DateTimeInterface::RFC3339_EXTENDED)
            : parent::ensureParameterIsPrimitive($parameter);
    }

    private static function encode(null|Cursor $cursor): null|string
    {
        return $cursor === null
            ? null
            : rtrim(strtr(base64_encode(json_encode($cursor->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
    }
}
