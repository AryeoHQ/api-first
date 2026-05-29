<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles\Store;

use \Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;
use Tests\Fixtures\Support\Schemas\ApiVersion;
use Workbench\App\Entities\Articles\Article;
use Workbench\App\Http\Api\V1;

final class Controller
{
    #[Route(
        name: 'api.v1.articles.store',
        uri: 'api/v1/articles',
        methods: Method::Post,
    )]
    public function __invoke(Authorizer $authorizer, Validator $validator): V1\Articles\Article
    {
        return Article::create([
            'title' => $validator->title,
            'body' => $validator->body,
        ])->toSchema(ApiVersion::V1);
    }
}
