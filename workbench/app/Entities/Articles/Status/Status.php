<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles\Status;

use Support\Database\Eloquent\StateMachines\Attributes\Events\Events;
use Support\Database\Eloquent\StateMachines\Attributes\Transitions\Transition;
use Support\Database\Eloquent\StateMachines\Contracts\StateMachineable;
use Support\Database\Eloquent\StateMachines\Provides\ManagesState;
use Workbench\App\Entities\Articles\Status\Events\Drafted;
use Workbench\App\Entities\Articles\Status\Events\Drafting;
use Workbench\App\Entities\Articles\Status\Events\Published;
use Workbench\App\Entities\Articles\Status\Events\Publishing;
use Workbench\App\Entities\Articles\Status\Triggers\Draft;
use Workbench\App\Entities\Articles\Status\Triggers\Publish;

enum Status: string implements StateMachineable
{
    use ManagesState;

    #[Events(before: Drafting::class, after: Drafted::class)]
    #[Transition(to: self::Published, using: Publish::class)]
    case Draft = 'draft';

    #[Events(before: Publishing::class, after: Published::class)]
    #[Transition(to: self::Draft, using: Draft::class)]
    case Published = 'published';
}
