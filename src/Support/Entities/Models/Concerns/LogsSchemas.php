<?php

declare(strict_types=1);

namespace Support\Entities\Models\Concerns;

use Illuminate\Support\Collection;
use Support\Http\Resources\Schemas\Concerns\TransformsToSchema;

/**
 * @mixin \Support\Events\Log\Contracts\Loggable
 */
trait LogsSchemas
{
    use TransformsToSchema;

    /** @return Collection<int, \Illuminate\Http\Resources\Json\JsonResource&\Support\Http\Resources\Schemas\Contracts\Schema> */
    public function toLoggable(): Collection
    {
        return $this->schemas->map(
            fn (string $schema) => $schema::make($this)
        );
    }
}
