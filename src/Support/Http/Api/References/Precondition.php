<?php

declare(strict_types=1);

namespace Support\Http\Api\References;

use Illuminate\Support\Stringable;
use Tooling\GeneratorCommands\References\GenericClass;

final class Precondition extends GenericClass
{
    public Stringable $stubPath {
        get => str(__DIR__.'/stubs/precondition.stub');
    }
}
