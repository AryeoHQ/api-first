<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeModel;

use Support\Entities\Models\Console\Commands\MakeModel as UpstreamMakeModel;
use Support\Http\Api\Console\Commands\MakeEvent\MakeEvent;

class MakeModel extends UpstreamMakeModel
{
    public function handle()
    {
        parent::handle();

        if ($this->option('events')) {
            $this->regenerateEventsWithRecordable();
        }
    }

    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $stub = $this->injectLoggableImports($stub);
        $stub = $this->injectLoggableInterfaces($stub);
        $stub = $this->injectLoggableTraits($stub);

        return $stub;
    }

    private function injectLoggableImports(string $stub): string
    {
        return str_replace(
            "use Support\\Entities\\Contracts\\Entity;\n",
            "use Support\\Entities\\Contracts\\Entity;\n"
            ."use Support\\Entities\\Models\\Concerns\\LogsSchemas;\n"
            ."use Support\\Events\\Log\\Contracts\\Loggable;\n"
            ."use Support\\Http\\Resources\\Schemas\\Contracts\\Schemable;\n",
            $stub,
        );
    }

    private function injectLoggableInterfaces(string $stub): string
    {
        return str_replace(
            'implements Entity',
            'implements Entity, Schemable, Loggable',
            $stub,
        );
    }

    private function injectLoggableTraits(string $stub): string
    {
        return str_replace(
            "    use HasUuids;\n",
            "    use HasUuids;\n    use LogsSchemas;\n",
            $stub,
        );
    }

    private function regenerateEventsWithRecordable(): void
    {
        foreach ($this->observableEvents() as $event) {
            $option = str_ends_with($event, 'ing')
                ? '--recordable'
                : '--recordable-after-commit';

            $this->call(MakeEvent::class, [
                'name' => $this->entity->event($event)->name,
                '--entity' => $this->entity->fqcn->toString(),
                '--force' => true,
                $option => true,
            ]);
        }
    }
}
