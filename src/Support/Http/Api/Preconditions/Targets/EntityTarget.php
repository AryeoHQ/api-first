<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions\Targets;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LogicException;
use Support\Database\Eloquent\Concurrency\ConcurrencyRevision;
use Support\Database\Eloquent\Concurrency\Contracts\LocksParentsFirst;
use Support\Database\Eloquent\Concurrency\Contracts\NamesConcurrencyRevision;
use Support\Database\Eloquent\Concurrency\Contracts\Revisioned;
use Support\Database\Eloquent\Concurrency\Revisions;
use Support\Http\Api\Preconditions\Contracts\Target;
use Support\Http\Api\Preconditions\Exceptions\ParentsChanged;
use Support\Http\Api\Preconditions\Exceptions\UnloadedConcurrencyRevision;
use Support\Http\Resources\Schemas\Contracts\Version;

final class EntityTarget implements Target
{
    private readonly Model&Revisioned $model;

    private readonly Version $api;

    /** @var list<NamesConcurrencyRevision> */
    private array $revisions = [];

    private bool $all = false;

    /** @var list<Model> */
    private array $locking = [];

    private function __construct(Model&Revisioned $model, Version $api)
    {
        $this->model = $model;
        $this->api = $api;
    }

    public static function for(Model&Revisioned $model, Version $api): self
    {
        return new self($model, $api);
    }

    public function comparingSelf(): static
    {
        return $this->comparingNamed(ConcurrencyRevision::Self);
    }

    public function comparingNamed(NamesConcurrencyRevision ...$revisions): static
    {
        $this->revisions = [...$this->revisions, ...array_values($revisions)];

        return $this;
    }

    public function comparingAll(): static
    {
        $this->all = true;

        return $this;
    }

    public function alsoLocking(Model ...$models): static
    {
        $connection = $this->model->getConnection()->getName();

        foreach ($models as $model) {
            throw_if(
                $model->getConnection()->getName() !== $connection,
                LogicException::class,
                sprintf(
                    'The target for [%s] on connection [%s] cannot also lock [%s] on connection [%s].',
                    $this->model::class,
                    $connection,
                    $model::class,
                    $model->getConnection()->getName(),
                ),
            );
        }

        $this->locking = [...$this->locking, ...array_values($models)];

        return $this;
    }

    public function apiVersion(): Version
    {
        return $this->api;
    }

    public function connection(): ConnectionInterface
    {
        return $this->model->getConnection();
    }

    public function lock(): void
    {
        $models = $this->inLockOrder([$this->model, ...$this->locking]);
        $parents = $this->parentsOf($models);

        foreach ($parents as $parent) {
            $this->lockRow($parent);
        }

        foreach ($models as $model) {
            $this->lockRow($model);
        }

        throw_if(
            array_map($this->identity(...), $this->parentsOf($models)) !== array_map($this->identity(...), $parents),
            ParentsChanged::class,
            sprintf('The parents of the target for [%s] changed before the lock.', $this->model::class),
        );
    }

    public function compared(): Revisions
    {
        throw_if(
            ! $this->all && $this->revisions === [],
            LogicException::class,
            sprintf('The target for [%s] compares no concurrency revision.', $this->model::class),
        );

        $own = null;
        $named = [];

        foreach ($this->revisions as $revision) {
            if ($revision === ConcurrencyRevision::Self) {
                $own = $this->revision($revision);
            } else {
                $named[(string) $revision->value] = $this->revision($revision);
            }
        }

        if (! $this->all) {
            return Revisions::of($own, $named);
        }

        return $this->model->concurrencyRevisions() ?? throw new UnloadedConcurrencyRevision(
            sprintf('The concurrency revisions of [%s] are not loaded.', $this->model::class),
        );
    }

    private function revision(NamesConcurrencyRevision $revision): int
    {
        throw_unless(
            array_key_exists($this->model->concurrencyRevisionColumn($revision), $this->model->getAttributes()),
            UnloadedConcurrencyRevision::class,
            sprintf('The [%s] concurrency revision of [%s] is not loaded.', $revision->value, $this->model::class),
        );

        return $this->model->concurrencyRevision($revision);
    }

    /**
     * @param  list<Model>  $models
     * @return list<Model>
     */
    private function inLockOrder(array $models): array
    {
        usort($models, fn (Model $left, Model $right): int => [$left->getMorphClass(), $left->getKey()] <=> [$right->getMorphClass(), $right->getKey()]);

        return $models;
    }

    /**
     * @param  list<Model>  $models
     * @return list<Model>
     */
    private function parentsOf(array $models): array
    {
        $parents = [];

        foreach ($models as $model) {
            if (! $model instanceof LocksParentsFirst) {
                continue;
            }

            foreach ($model->parentsToLockFirst() as $parent) {
                if (! in_array($this->identity($parent), array_map($this->identity(...), $parents), true)) {
                    $parents[] = $parent;
                }
            }
        }

        return $this->inLockOrder($parents);
    }

    /**
     * @return array{string, mixed}
     */
    private function identity(Model $model): array
    {
        $key = $model->getKey();

        return [$model->getMorphClass(), is_scalar($key) ? (string) $key : $key];
    }

    private function lockRow(Model $model): void
    {
        $query = $model->newQuery();

        if (method_exists($model, 'trashed') && $model->trashed()) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        $locked = $query->whereKey($model->getKey())->lockForUpdate()->firstOrFail();

        $model->setRawAttributes($locked->getAttributes(), true);
    }
}
