<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Errors;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Tests\Fixtures\Support\Articles\Article;

final class HiddenArticlePolicy
{
    public function view(null|Authenticatable $user, Article $article): Response
    {
        return Response::denyAsNotFound();
    }
}
