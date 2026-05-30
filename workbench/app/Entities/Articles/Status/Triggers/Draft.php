<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles\Status\Triggers;

use Support\Database\Eloquent\StateMachines\Triggers\Target\Target;
use Support\Database\Eloquent\StateMachines\Triggers\Trigger;
use Workbench\App\Entities\Articles\Article;

final class Draft extends Trigger
{
    #[Target]
    protected readonly Article $article;

    public function allowed(): bool
    {
        return $this->article->is_not_syndicated;
    }

    public function handle(): void {}
}
