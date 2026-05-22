<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeResource\Listeners;

use Support\Http\Resources\Schemas\Console\Commands\MakeResource\Events\BuildingSchema;

class InjectSchemaProperties
{
    public function handle(BuildingSchema $event): void
    {
        $resourceType = str(class_basename($event->fqcn->toString()))->snake();

        /** @var class-string $versionClass */
        $versionClass = config('api-resource-schema.version');
        $versionBasename = class_basename($versionClass);

        $event->properties->push('public string $id { get => $this->resource->getKey(); }');
        $event->properties->push("public string \$resourceType = '".$resourceType."';");
        $event->properties->push("public {$versionBasename} \$resourceVersion { get => \$this->schemaVersion; }");
    }
}
