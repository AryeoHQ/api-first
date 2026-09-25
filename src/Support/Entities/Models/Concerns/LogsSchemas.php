<?php

declare(strict_types=1);

namespace Support\Entities\Models\Concerns;

use Support\Events\Log\Logs\Data\Data;
use Support\Events\Log\Logs\Data\Variant;
use Support\Http\Resources\Schemas\Concerns\TransformsToSchema;
use Support\Http\Resources\Schemas\Contracts\Version;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait LogsSchemas
{
    use TransformsToSchema;

    public function toLoggable(): Data
    {
        return Data::of(
            ...$this->schemaVersions->map(
                fn (Version $version): Variant => Variant::make($this->toSchema($version))
            )
        );
    }
}
