<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Support\Entities\Events\Contracts\ForEntity;
use Support\Entities\Events\IdentifiesEntity\IdentifiesEntity;
use Support\Entities\Events\Provides\HasEntity;
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\RecordableAfterCommit;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Workbench\App\Entities\Articles\Article;

#[Alias('article.deleted')]
final class Deleted implements ForEntity, RecordableAfterCommit
{
    use Dispatchable;
    use HasEntity;
    use HasLoggable;

    #[IdentifiesEntity]
    #[IdentifiesLoggable]
    public readonly Article $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }
}
