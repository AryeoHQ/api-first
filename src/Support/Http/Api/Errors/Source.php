<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use JsonSerializable;

final readonly class Source implements JsonSerializable
{
    private function __construct(
        private string $key,
        private string $value,
    ) {}

    public static function pointer(string $pointer): self
    {
        return new self('pointer', $pointer);
    }

    public static function parameter(string $parameter): self
    {
        return new self('parameter', $parameter);
    }

    public static function header(string $header): self
    {
        return new self('header', $header);
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return [$this->key => $this->value];
    }
}
