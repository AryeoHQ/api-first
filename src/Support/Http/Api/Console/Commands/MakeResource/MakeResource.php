<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeResource;

use Illuminate\Console\Command;
use Support\Entities\Console\Concerns\RetrievesEntity;
use Support\Entities\References\Entity;
use Support\Http\Resources\Schemas\Attributes\UseSchema\UseSchema;
use Support\Http\Resources\Schemas\Attributes\UseSchemaCollection\UseSchemaCollection;
use Support\Http\Resources\Schemas\Console\Commands\MakeResource\MakeResource as UpstreamMakeResource;
use Support\Http\Resources\Schemas\Console\Commands\MakeResource\References\Schema;
use Support\Http\Resources\Schemas\Contracts\Version;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\select;

class MakeResource extends Command
{
    /** @use RetrievesEntity<Entity> */
    use RetrievesEntity;

    protected $name = 'make:resource';

    protected $description = 'Create a new API resource schema for an entity.';

    public function handle(): int
    {
        $this->resolveEntity();

        $version = $this->resolveVersion();

        if ($version === null) {
            return self::FAILURE;
        }

        $result = $this->call(
            UpstreamMakeResource::class,
            [
                'name' => $this->entity->name->toString(),
                '--namespace' => $this->entity->baseNamespace->ltrim('\\')->append('\\Http\\Api')->toString(),
                '--force' => $this->option('force'),
                '--schema-version' => $version->name,
            ]
        );

        if ($result !== self::SUCCESS) {
            return $result;
        }

        $schema = resolve(Schema::class, [
            'name' => $this->entity->name,
            'baseNamespace' => $this->entity->baseNamespace->ltrim('\\')->append('\\Http\\Api\\', $version->name),
        ]);

        $this->components->warn('Reminder:');
        $this->components->bulletList([
            'Add #['.class_basename(UseSchema::class).'('.class_basename($schema->fqcn->classBasename()).'::class)] to '.class_basename($this->entity->fqcn->classBasename()->toString()).'.',
            'Add #['.class_basename(UseSchemaCollection::class).'('.class_basename($schema->collection->fqcn->classBasename())."::class)] to {$this->entity->plural}.",
        ]);

        return self::SUCCESS;
    }

    private function resolveVersion(): null|Version
    {
        /** @var class-string<\Support\Http\Resources\Schemas\Contracts\Version>|null $enumClass */
        $enumClass = config('api-resource-schema.version', null);

        if (! $enumClass) {
            $this->components->error('`Version` enum not configured.');

            return null;
        }

        $cases = collect($enumClass::cases());

        if ($input = $this->option('api-version')) {
            return $cases->first(
                fn (Version $case) => $case->name === $input || $case->value === $input,
            );
        }

        $selected = select(
            label: 'Select a version.',
            options: $cases->mapWithKeys(fn (Version $case) => [$case->value => $case->value])->toArray(),
        );

        return $cases->first(fn (Version $case) => $case->value === $selected);
    }

    /** @return array<int, InputOption> */
    protected function getOptions(): array
    {
        return [
            ...$this->getEntityInputOptions(),
            new InputOption('api-version', null, InputOption::VALUE_OPTIONAL, 'The version for this schema'),
            new InputOption('force', 'f', InputOption::VALUE_NONE, 'Create the class even if it already exists'),
        ];
    }
}
