<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController\Contracts;

use Illuminate\Support\Stringable;

interface GeneratesForApiVersion
{
    public Stringable $apiVersion { get; }

    public function resolveApiVersion(): void;

    public function apiVersionFromPrompt(): Stringable;
}
