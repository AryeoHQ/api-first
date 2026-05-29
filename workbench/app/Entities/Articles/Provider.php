<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles;

use Illuminate\Database\Eloquent\Relations\Relation;
use Workbench\App\Entities\Articles\Article;

final class Provider extends \Illuminate\Support\ServiceProvider
{
    public function boot(): void
    {
        Relation::morphMap([
            'article' => Article::class,
        ]);
    }
}
