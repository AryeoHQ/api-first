<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Support\Entities\Events\Contracts\ForEntity;
use Support\Entities\Events\IdentifiesEntity\IdentifiesEntity;
use Support\Entities\Events\Provides\HasEntity;
use Workbench\App\Entities\Articles\Article;

final class Retrieved implements ForEntity
{
    use Dispatchable;
    use HasEntity;

    #[IdentifiesEntity]
    public readonly Article $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }
}
