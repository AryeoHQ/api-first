<?php

declare(strict_types=1);

namespace Support\Http\Api\Pagination;

use Illuminate\Encryption\MissingAppKeyException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class CursorSignature
{
    public static function fingerprint(Request $request): string
    {
        parse_str(Arr::query(Arr::only($request->query->all(), ['sort', 'filters', 'query'])), $bound);

        return json_encode([$request->path(), $bound['sort'] ?? null, self::sortKeys($bound['filters'] ?? null), $bound['query'] ?? null], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
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

    private static function sortKeys(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        ksort($value, SORT_STRING);

        return array_map(self::sortKeys(...), $value);
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
