<?php

declare(strict_types=1);

namespace Support\Http\Api\Pagination;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\MissingAppKeyException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class CursorSignature
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public static function fingerprint(Builder $query): string
    {
        $orders = collect($query->getQuery()->unionOrders)
            ->whenEmpty(fn (): Collection => collect($query->getQuery()->orders))
            ->whenEmpty(fn (): Collection => collect([['column' => $query->getModel()->getQualifiedKeyName(), 'direction' => 'asc']]))
            ->filter(fn (array $order): bool => Arr::has($order, 'direction'))
            ->map(fn (array $order): array => [(string) $order['column'], (string) $order['direction']])
            ->values()
            ->all();

        return json_encode([$query->getModel()->getTable(), $orders], JSON_THROW_ON_ERROR);
    }

    public static function sign(string $encoded, string $fingerprint): string
    {
        $mac = hash_hmac('sha256', $fingerprint."\n".$encoded, self::key(), true);

        return $encoded.'.'.rtrim(strtr(base64_encode($mac), '+/', '-_'), '=');
    }

    public static function verify(string $signed, string $fingerprint): null|string
    {
        $encoded = Str::beforeLast($signed, '.');

        return hash_equals(self::sign($encoded, $fingerprint), $signed) ? $encoded : null;
    }

    private static function key(): string
    {
        $key = config('app.key');

        if (! is_string($key) || $key === '') {
            throw new MissingAppKeyException;
        }

        return Str::startsWith($key, 'base64:') ? Str::fromBase64(Str::after($key, 'base64:')) : $key;
    }
}
