<?php

declare(strict_types=1);

namespace Support\Http\Api\Rules;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

final class Cursor implements ValidationRule
{
    private const array DATE_FORMATS = [
        DateTimeInterface::RFC3339_EXTENDED,
        'Y-m-d\TH:i:s.uP',
        'Y-m-d H:i:s',
    ];

    /** @var null|list<string> */
    private null|array $parameters;

    /** @var list<string> */
    private array $dateFormats;

    /**
     * @param  null|list<string>  $parameters
     * @param  list<string>  $dateFormats
     */
    public function __construct(null|array $parameters = null, array $dateFormats = [])
    {
        $this->parameters = $parameters;
        $this->dateFormats = [...self::DATE_FORMATS, ...$dateFormats];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parameters = self::decode($value);

        if ($parameters === null || ! $this->accepts($parameters)) {
            $fail('The :attribute is not a cursor that this list issued.');
        }
    }

    /**
     * @return null|array<array-key, mixed>
     */
    private static function decode(mixed $value): null|array
    {
        $json = is_string($value) ? base64_decode(str_replace(['-', '_'], ['+', '/'], $value), true) : false;
        $decoded = $json === false ? null : json_decode($json, true);

        if (! is_array($decoded) || ! is_bool($decoded['_pointsToNextItems'] ?? null)) {
            return null;
        }

        return Arr::except($decoded, '_pointsToNextItems');
    }

    /**
     * @param  array<array-key, mixed>  $parameters
     */
    private function accepts(array $parameters): bool
    {
        return collect($parameters)->every(fn (mixed $parameter): bool => is_scalar($parameter) && $this->isNotAnImpossibleDate($parameter))
            && $this->hasExpectedNames($parameters);
    }

    private function isNotAnImpossibleDate(bool|float|int|string $parameter): bool
    {
        if (! is_string($parameter)) {
            return true;
        }

        $parsed = collect($this->dateFormats)
            ->mapWithKeys(fn (string $format): array => [$format => DateTimeImmutable::createFromFormat('!'.$format, $parameter)])
            ->filter();

        return $parsed->isEmpty()
            || $parsed->contains(fn (DateTimeImmutable $date, string $format): bool => $date->format($format) === $parameter);
    }

    /**
     * @param  array<array-key, mixed>  $parameters
     */
    private function hasExpectedNames(array $parameters): bool
    {
        if ($this->parameters === null) {
            return true;
        }

        return collect($this->parameters)->unique()->sort()->values()->all()
            === collect(array_keys($parameters))->map(fn (int|string $name): string => (string) $name)->sort()->values()->all();
    }
}
