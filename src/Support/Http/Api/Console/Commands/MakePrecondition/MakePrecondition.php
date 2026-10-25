<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakePrecondition;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Stringable;
use Support\Entities\Console\Concerns\RetrievesEntityFromOption;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Commands\MakeController\Concerns\ResolvesApiVersion;
use Support\Http\Api\Preconditions\Contracts\Target;
use Support\Http\Api\Preconditions\Precondition as BasePrecondition;
use Support\Http\Api\Preconditions\Targets\EntityTarget;
use Support\Http\Api\References\Precondition;
use Symfony\Component\Console\Input\InputOption;
use Tooling\GeneratorCommands\Concerns\CreatesColocatedTests;
use Tooling\GeneratorCommands\Concerns\GeneratorCommandCompatibility;
use Tooling\GeneratorCommands\Concerns\RetrievesNamespace;
use Tooling\GeneratorCommands\Contracts\GeneratesFile;
use Tooling\GeneratorCommands\References\Contracts\Reference;

class MakePrecondition extends GeneratorCommand implements GeneratesFile
{
    use CreatesColocatedTests;
    use GeneratorCommandCompatibility;
    use ResolvesApiVersion;
    use RetrievesEntityFromOption;
    use RetrievesNamespace;

    protected $name = 'make:precondition';

    protected $description = 'Create a new precondition class';

    protected $type = 'Precondition';

    protected Entity $entity;

    public Stringable $nameInput {
        get => $this->nameInput ??= str($this->argument('name'));
    }

    public Reference $reference {
        get => $this->reference ??= resolve(Precondition::class, [
            'name' => $this->nameInput,
            'baseNamespace' => $this->baseNamespace,
        ]);
    }

    public function handle(): null|bool
    {
        $this->resolveNamespace();
        $this->resolveApiVersion();

        $this->entity = Entity::fromFqcn(
            $this->entityFromOption() ?? str(\Laravel\Prompts\text(label: 'Which entity? (FQCN)', required: true))
        );

        return parent::handle();
    }

    protected function buildClass($name)
    {
        return str_replace([
            '{{ imports }}',
            '{{ entity }}',
            '{{ variable }}',
            '{{ routeParameter }}',
            '{{ version }}',
        ], [
            $this->buildImports(),
            $this->entity->name->toString(),
            $this->entity->name->camel()->toString(),
            $this->entity->name->snake()->toString(),
            class_basename($this->version::class).'::'.$this->version->name,
        ], parent::buildClass($name));
    }

    private function buildImports(): string
    {
        return collect([
            $this->entity->fqcn->ltrim('\\')->toString(),
            $this->version::class,
            Target::class,
            BasePrecondition::class.' as BasePrecondition',
            EntityTarget::class,
        ])->sort()->map(fn (string $import): string => "use {$import};")->implode("\n");
    }

    /** @return array<int, InputOption> */
    protected function getOptions(): array
    {
        return [
            new InputOption('force', 'f', InputOption::VALUE_NONE, 'Create the class even if it already exists'),
            ...$this->getNamespaceInputOptions(),
            ...$this->getApiVersionInputOptions(),
            ...$this->getEntityInputOptions(),
        ];
    }
}
