<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions\Contracts;

use Illuminate\Database\ConnectionInterface;
use Support\Http\Resources\Schemas\Tags\Tag;

interface Target
{
    public function tag(): Tag;

    public function connection(): ConnectionInterface;

    public function lock(): void;
}
