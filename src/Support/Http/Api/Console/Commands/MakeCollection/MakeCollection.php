<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeCollection;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Support\Http\Resources\Schemas\Concerns\TransformsToSchemaCollection;
use Support\Http\Resources\Schemas\Contracts\SchemableCollection;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:collection')]
class MakeCollection extends \Support\Entities\Models\Console\Commands\MakeCollection
{
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $stub = $this->injectSchemableCollectionImports($stub);
        $stub = $this->injectSchemableCollectionInterface($stub);
        $stub = $this->injectSchemableCollectionBody($stub);

        return $stub;
    }

    private function injectSchemableCollectionImports(string $stub): string
    {
        return str_replace(
            'use '.EloquentCollection::class.";\n",
            'use '.EloquentCollection::class.";\n"
            .'use '.TransformsToSchemaCollection::class.";\n"
            .'use '.SchemableCollection::class.";\n",
            $stub,
        );
    }

    private function injectSchemableCollectionInterface(string $stub): string
    {
        return str_replace(
            'extends '.class_basename(EloquentCollection::class).' {}',
            'extends '.class_basename(EloquentCollection::class).' implements '.class_basename(SchemableCollection::class),
            $stub,
        );
    }

    private function injectSchemableCollectionBody(string $stub): string
    {
        return str_replace(
            'extends '.class_basename(EloquentCollection::class).' implements '.class_basename(SchemableCollection::class),
            'extends '.class_basename(EloquentCollection::class).' implements '.class_basename(SchemableCollection::class)."\n{\n    use ".class_basename(TransformsToSchemaCollection::class).";\n}",
            $stub,
        );
    }
}
