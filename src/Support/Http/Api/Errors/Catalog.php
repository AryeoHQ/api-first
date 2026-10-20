<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use Illuminate\Support\Arr;

final readonly class Catalog
{
    /**
     * @param  list<mixed>  $codes
     */
    private function __construct(
        private array $codes,
    ) {}

    public static function load(): self
    {
        $codes = require __DIR__.'/../../../../../resources/error-codes.php';

        return new self([...Arr::wrap($codes), ...Arr::wrap(config('api-first.errors.codes', []))]);
    }

    public function has(string $code): bool
    {
        return in_array($code, $this->codes, true);
    }
}
