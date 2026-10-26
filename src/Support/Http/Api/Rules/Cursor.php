<?php

declare(strict_types=1);

namespace Support\Http\Api\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Support\Http\Api\Pagination\CursorSignature;

final class Cursor implements ValidationRule
{
    public const int MAX_LENGTH = 1024;

    private null|string $fingerprint;

    public function __construct(null|string $fingerprint = null)
    {
        $this->fingerprint = $fingerprint;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parameters = self::decode($this->payload($value));

        if ($parameters === null || ! self::accepts($parameters)) {
            $fail('The :attribute is not a cursor that this list issued.');
        }
    }

    private function payload(mixed $value): null|string
    {
        if (! is_string($value) || strlen($value) > self::MAX_LENGTH || preg_match('/\A[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\z/', $value) !== 1) {
            return null;
        }

        return $this->fingerprint === null ? Str::before($value, '.') : CursorSignature::verify($value, $this->fingerprint);
    }

    /**
     * @return null|array<array-key, mixed>
     */
    private static function decode(null|string $payload): null|array
    {
        $json = $payload === null ? false : base64_decode(strtr($payload, '-_', '+/'), true);
        $decoded = $json === false ? null : json_decode($json, true);

        if (! is_array($decoded) || ! is_bool($decoded['_pointsToNextItems'] ?? null)) {
            return null;
        }

        return Arr::except($decoded, '_pointsToNextItems');
    }

    /**
     * @param  array<array-key, mixed>  $parameters
     */
    private static function accepts(array $parameters): bool
    {
        return collect($parameters)->every(fn (mixed $parameter): bool => is_scalar($parameter) && ! str_contains((string) $parameter, "\0"));
    }
}
