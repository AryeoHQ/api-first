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

        $locked = $query->whereKey($this->entity->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $this->entity->setRawAttributes($locked->getAttributes(), true);
    }

    private function trashed(): bool
    {
        return method_exists($this->entity, 'trashed') && $this->entity->trashed();
    }
}
