<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeModel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Support\Entities\Contracts\Entity;
use Support\Entities\Models\Concerns\LogsSchemas;
use Support\Events\Log\Contracts\Loggable;
use Support\Http\Api\Console\Commands\MakeEvent\MakeEvent;
use Support\Http\Resources\Schemas\Contracts\Schemable;

class MakeModel extends \Support\Entities\Models\Console\Commands\MakeModel
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
            'use '.Entity::class.";\n",
            'use '.Entity::class.";\n"
            .'use '.LogsSchemas::class.";\n"
            .'use '.Loggable::class.";\n"
            .'use '.Schemable::class.";\n",
            $stub,
        );
    }

    private function injectLoggableInterfaces(string $stub): string
    {
        return str_replace(
            'implements '.class_basename(Entity::class),
            'implements '.class_basename(Entity::class).', '.class_basename(Schemable::class).', '.class_basename(Loggable::class),
            $stub,
        );
    }

    private function injectLoggableTraits(string $stub): string
    {
        return str_replace(
            '    use '.class_basename(HasUuids::class).";\n",
            '    use '.class_basename(HasUuids::class).";\n    use ".class_basename(LogsSchemas::class).";\n",
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
