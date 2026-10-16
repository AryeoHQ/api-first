<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions\Targets;

use Illuminate\Database\Eloquent\Model;
use Support\Http\Api\Preconditions\Contracts\Target;
use Support\Http\Resources\Schemas\Contracts\Schemable;
use Support\Http\Resources\Schemas\Contracts\Version;
use Support\Http\Resources\Schemas\Tags\Tag;

final readonly class EntityTarget implements Target
{
    public function __construct(
        private Model&Schemable $entity,
        private Version $version,
    ) {}

    public function tag(): Tag
    {
        return $this->entity->toTag($this->version);
    }

    public function lock(): void
    {
        $locked = $this->entity->newQueryWithoutScopes()
            ->whereKey($this->entity->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $this->entity->setRawAttributes($locked->getAttributes(), true);
        $this->entity->load(array_keys($this->entity->getRelations()));
    }
}
