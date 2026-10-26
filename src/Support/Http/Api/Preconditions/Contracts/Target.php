<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions\Contracts;

use Illuminate\Database\ConnectionInterface;
use Support\Database\Eloquent\Concurrency\Revisions;
use Support\Http\Resources\Schemas\Contracts\Version;

interface Target
{
    public function apiVersion(): Version;

    public function connection(): ConnectionInterface;

    public function lock(): void;

    public function compared(): Revisions;
}
