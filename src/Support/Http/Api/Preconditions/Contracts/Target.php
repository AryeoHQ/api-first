<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions\Contracts;

use Illuminate\Database\ConnectionInterface;
use Support\Http\Resources\Schemas\Contracts\Version;

interface Target
{
    public function apiVersion(): Version;

    public function connection(): ConnectionInterface;

    public function lock(): void;

    /**
     * @return array<string, int>
     */
    public function compared(): array;
}
