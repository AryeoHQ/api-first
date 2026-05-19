<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeCollection;

use Support\Entities\Models\Console\Commands\MakeCollection as UpstreamMakeCollection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:collection')]
class MakeCollection extends UpstreamMakeCollection
{
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $stub = $this->injectSchemableCollectionImports($stub);
        $stub = $this->injectSchemableCollectionInterface($stub);
        $stub = $this->injectSchemableCollectionBody($stub);

        if ($schemaCollection = $this->option('schema-collection')) {
            $stub = $this->injectUseSchemaCollectionAttribute($stub, $schemaCollection);
        }

        return $stub;
    }

    private function injectSchemableCollectionImports(string $stub): string
    {
        return str_replace(
            "use Illuminate\\Database\\Eloquent\\Collection;\n",
            "use Illuminate\\Database\\Eloquent\\Collection;\n"
            ."use Support\\Http\\Resources\\Schemas\\Concerns\\TransformsToSchemaCollection;\n"
            ."use Support\\Http\\Resources\\Schemas\\Contracts\\SchemableCollection;\n",
            $stub,
        );
    }

    private function injectSchemableCollectionInterface(string $stub): string
    {
        return str_replace(
            'extends Collection {}',
            'extends Collection implements SchemableCollection',
            $stub,
        );
    }

    private function injectSchemableCollectionBody(string $stub): string
    {
        return str_replace(
            'extends Collection implements SchemableCollection',
            "extends Collection implements SchemableCollection\n{\n    use TransformsToSchemaCollection;\n}",
            $stub,
        );
    }

    private function injectUseSchemaCollectionAttribute(string $stub, string $schemaCollectionFqcn): string
    {
        $alias = 'SchemaCollection'.class_basename($schemaCollectionFqcn);

        $stub = str_replace(
            "use Support\\Http\\Resources\\Schemas\\Contracts\\SchemableCollection;\n",
            "use Support\\Http\\Resources\\Schemas\\Contracts\\SchemableCollection;\n"
            ."use Support\\Http\\Resources\\Schemas\\Attributes\\UseSchemaCollection\\UseSchemaCollection;\n"
            ."use {$schemaCollectionFqcn} as {$alias};\n",
            $stub,
        );

        $stub = str_replace(
            'final class',
            "#[UseSchemaCollection({$alias}::class)]\nfinal class",
            $stub,
        );

        return $stub;
    }

    /** @return array<int, InputOption> */
    protected function getOptions(): array
    {
        return [
            ...parent::getOptions(),
            new InputOption('schema-collection', null, InputOption::VALUE_OPTIONAL, 'The schema collection FQCN to reference via #[UseSchemaCollection]'),
        ];
    }
}
