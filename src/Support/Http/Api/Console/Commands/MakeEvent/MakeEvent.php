<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeEvent;

use Support\Entities\Events\Contracts\ForEntity;
use Support\Entities\Events\IdentifiesEntity\IdentifiesEntity;
use Support\Entities\Events\Provides\HasEntity;
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\Contracts\RecordableAfterCommit;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Symfony\Component\Console\Input\InputOption;

class MakeEvent extends \Support\Entities\Models\Console\Commands\MakeEvent
{
    protected function buildClass($name)
    {
        return with(
            parent::buildClass($name),
            fn (string $stub) => match (true) {
                $this->option('recordable-after-commit') => $this->injectRecordable($stub, recordableAfterCommit: true),
                $this->option('recordable') => $this->injectRecordable($stub, recordableAfterCommit: false),
                default => $stub,
            }
        );
    }

    private function injectRecordable(string $stub, bool $recordableAfterCommit): string
    {
        $contractClass = $recordableAfterCommit ? RecordableAfterCommit::class : Recordable::class;
        $contractBasename = class_basename($contractClass);

        $stub = str_replace(
            'use '.ForEntity::class.";\n",
            'use '.ForEntity::class.";\n"
            .'use '.Alias::class.";\n"
            .'use '.$contractClass.";\n"
            .'use '.IdentifiesLoggable::class.";\n"
            .'use '.HasLoggable::class.";\n",
            $stub,
        );

        $stub = str_replace(
            'final class',
            "#[".class_basename(Alias::class)."('{$this->reference->semanticName}')]\nfinal class",
            $stub,
        );

        $stub = str_replace(
            'implements '.class_basename(ForEntity::class),
            'implements '.class_basename(ForEntity::class).', '.$contractBasename,
            $stub,
        );

        $stub = str_replace(
            '    use '.class_basename(HasEntity::class).";\n",
            '    use '.class_basename(HasEntity::class).";\n    use ".class_basename(HasLoggable::class).";\n",
            $stub,
        );

        $stub = str_replace(
            '    #['.class_basename(IdentifiesEntity::class)."]\n",
            '    #['.class_basename(IdentifiesEntity::class)."]\n    #[".class_basename(IdentifiesLoggable::class)."]\n",
            $stub,
        );

        return $stub;
    }

    /** @return array<int, InputOption> */
    protected function getOptions(): array
    {
        return [
            ...parent::getOptions(),
            new InputOption('recordable', null, InputOption::VALUE_NONE, 'Implement the '.class_basename(Recordable::class).' contract'),
            new InputOption('recordable-after-commit', null, InputOption::VALUE_NONE, 'Implement the '.class_basename(RecordableAfterCommit::class).' contract'),
        ];
    }
}
