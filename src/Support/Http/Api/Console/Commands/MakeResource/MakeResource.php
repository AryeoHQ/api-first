<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeResource;

use Illuminate\Console\Command;
use Support\Entities\Console\Concerns\RetrievesEntity;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Commands\MakeController\Concerns\ResolvesApiVersion;
use Support\Http\Resources\Schemas\Console\Commands\MakeResource\MakeResource as UpstreamMakeResource;
use Symfony\Component\Console\Input\InputOption;
use Tooling\GeneratorCommands\Concerns\SearchesClasses;

class MakeResource extends Command
{
    use ResolvesApiVersion;

    /** @use RetrievesEntity<Entity> */
    use RetrievesEntity;

    use SearchesClasses;

    protected $name = 'make:resource';

    protected $description = 'Create a new API resource schema for an entity.';

    public function handle(): int
    {
        $this->resolveApiVersion();
        $this->resolveEntity();

        $namespace = $this->entity->baseNamespace->ltrim('\\')->append(
            '\\Http\\Api\\', $this->apiVersion->toString(),
            '\\',
            $this->entity->plural->toString()
        )->toString();

        return $this->call(
            UpstreamMakeResource::class,
            [
                'name' => $this->entity->name->toString(),
                '--namespace' => $namespace,
                '--force' => $this->option('force'),
            ]
        );
    }

    /** @return array<int, InputOption> */
    protected function getOptions(): array
    {
        return [
            ...$this->getApiVersionInputOptions(),
            ...$this->getEntityInputOptions(),
            new InputOption('force', 'f', InputOption::VALUE_NONE, 'Create the class even if it already exists'),
        ];
    }
}
