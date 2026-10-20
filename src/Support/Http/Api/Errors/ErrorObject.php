<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use JsonSerializable;

final readonly class ErrorObject implements JsonSerializable
{
    public function __construct(
        private string $code,
        private string $detail,
        private null|Source $source = null,
    ) {}

    /**
     * @return array{code: string, detail: string, source?: Source}
     */
    public function jsonSerialize(): array
    {
        $error = ['code' => $this->code, 'detail' => $this->detail];

        if ($this->source !== null) {
            $error['source'] = $this->source;
        }

        return $error;
    }
}
