<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles\Policy;

use Illuminate\Contracts\Auth\Authenticatable;
use Workbench\App\Entities\Articles\Article;

final class Policy
{
    public function view(Authenticatable $user, Article $article): bool
    {
        return true;
    }
}
