<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles\Syndicate;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Support\Routing\Attributes\Middleware;
use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;
use Tests\Fixtures\Support\Schemas\ApiVersion;
use Workbench\App\Entities\Articles\Article;
use Workbench\App\Http\Api\V1;

final class Controller
{
    #[Route(
        name: 'api.v1.articles.actions.syndicate',
        uri: 'api/v1/articles/{article}/actions/syndicate',
        methods: Method::Post,
    )]
    #[Middleware(SubstituteBindings::class)]
    public function __invoke(Authorizer $authorizer, Validator $validator, Article $article): V1\Articles\Article
    {
        return $article->syndicate()->now()->toSchema(ApiVersion::V1);
    }
}
