<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles\Actions;

use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;
use Workbench\App\Entities\Articles\Article;

final class Syndicate implements Action
{
    use AsAction;

    public readonly Article $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    public function handle(): Article
    {
        // Syndication request using LaravelNews API SDK...

        $this->article->update([
            'syndicated_at' => now(),
        ]);

        return $this->article;
    }
}
