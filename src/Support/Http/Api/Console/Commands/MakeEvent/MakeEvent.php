<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeEvent;

use Support\Entities\Models\Console\Commands\MakeEvent as UpstreamMakeEvent;
use Symfony\Component\Console\Input\InputOption;

class MakeEvent extends UpstreamMakeEvent
{
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        if ($this->option('recordable-after-commit')) {
            $stub = $this->injectRecordable($stub, recordableAfterCommit: true);
        } elseif ($this->option('recordable')) {
            $stub = $this->injectRecordable($stub, recordableAfterCommit: false);
        }

        return $stub;
    }

    private function injectRecordable(string $stub, bool $recordableAfterCommit): string
    {
        $contract = $recordableAfterCommit
            ? 'RecordableAfterCommit'
            : 'Recordable';

        // Add imports
        $stub = str_replace(
            "use Support\\Entities\\Events\\Contracts\\ForEntity;\n",
            "use Support\\Entities\\Events\\Contracts\\ForEntity;\n"
            ."use Support\\Events\\Log\\Alias\\Alias;\n"
            ."use Support\\Events\\Log\\Contracts\\{$contract};\n"
            ."use Support\\Events\\Log\\IdentifiesLoggable\\IdentifiesLoggable;\n"
            ."use Support\\Events\\Log\\Provides\\HasLoggable;\n",
            $stub,
        );

        // Add class-level attribute
        $stub = str_replace(
            'final class',
            "#[Alias('{$this->reference->semanticName}')]\nfinal class",
            $stub,
        );

        // Add interface
        $stub = str_replace(
            'implements ForEntity',
            "implements ForEntity, {$contract}",
            $stub,
        );

        // Add HasLoggable trait
        $stub = str_replace(
            "    use HasEntity;\n",
            "    use HasEntity;\n    use HasLoggable;\n",
            $stub,
        );

        // Add #[IdentifiesLoggable] attribute to model property
        $stub = str_replace(
            "    #[IdentifiesEntity]\n",
            "    #[IdentifiesEntity]\n    #[IdentifiesLoggable]\n",
            $stub,
        );

        return $stub;
    }

    /** @return array<int, InputOption> */
    protected function getOptions(): array
    {
        return [
            ...parent::getOptions(),
            new InputOption('recordable', null, InputOption::VALUE_NONE, 'Implement the Recordable contract'),
            new InputOption('recordable-after-commit', null, InputOption::VALUE_NONE, 'Implement the RecordableAfterCommit contract'),
        ];
    }
}
