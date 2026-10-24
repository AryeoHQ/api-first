<?php

declare(strict_types=1);

namespace Support\Http\Api\Preconditions\Targets;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LogicException;
use Support\Database\Eloquent\Concurrency\ConcurrencyRevision;
use Support\Database\Eloquent\Concurrency\Contracts\NamesConcurrencyRevision;
use Support\Database\Eloquent\Concurrency\Contracts\Revisioned;
use Support\Database\Eloquent\Concurrency\Exceptions\UndeclaredConcurrencyRevision;
use Support\Http\Api\Preconditions\Contracts\Target;
use Support\Http\Resources\Schemas\Contracts\Version;

final class EntityTarget implements Target
{
    /** @var array<string, string> */
    private array $compared = [];

    /** @var list<Model> */
    private array $locking = [];

    private function __construct(
        private readonly Model&Revisioned $model,
        private readonly Version $api,
    ) {}

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
        $columns = $this->model->concurrencyRevisionColumns();

        foreach ($revisions as $revision) {
            $name = (string) $revision->value;

            throw_unless(array_key_exists($name, $columns), UndeclaredConcurrencyRevision::class, $this->model, $revision);

            $this->compared[$name] = $columns[$name];
        }

        return $this;
    }

    public function comparingAll(): static
    {
        $this->compared += $this->model->concurrencyRevisionColumns();

        return $this;
    }

    public function alsoLocking(Model ...$models): static
    {
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
        $models = [$this->model, ...$this->locking];

        usort($models, fn (Model $a, Model $b): int => [$a->getKey(), $a->getTable()] <=> [$b->getKey(), $b->getTable()]);

        foreach ($models as $model) {
            $this->lockRow($model);
        }
    }

    /**
     * @return array<string, int>
     */
    public function compared(): array
    {
        throw_if(
            $this->compared === [],
            LogicException::class,
            sprintf('The target for [%s] compares no concurrency revision.', $this->model::class),
        );

        return array_map(fn (string $column): int => intval($this->model->getAttribute($column)), $this->compared);
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
