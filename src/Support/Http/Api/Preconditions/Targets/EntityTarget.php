<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions\Targets;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Support\Http\Api\Preconditions\Contracts\Target;
use Support\Http\Resources\Schemas\Contracts\Schemable;
use Support\Http\Resources\Schemas\Contracts\Version;
use Support\Http\Resources\Schemas\Tags\Tag;

final class EntityTarget implements Target
{
    private null|(Model&Schemable) $locked = null;

    public function __construct(
        private readonly Model&Schemable $entity,
        private readonly Version $version,
    ) {}

    public function tag(): Tag
    {
        return ($this->locked ?? $this->entity)->toTag($this->version);
    }

    public function connection(): ConnectionInterface
    {
        return $this->entity->getConnection();
    }

    public function lock(): void
    {
        $query = $this->entity->newQuery();

        if ($this->trashed()) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        /** @var Model&Schemable $locked */
        $locked = $query->whereKey($this->entity->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $this->locked = $locked;
        $this->entity->setRawAttributes($locked->getAttributes(), true);
    }

    private function trashed(): bool
    {
        return method_exists($this->entity, 'trashed') && $this->entity->trashed();
    }
}
