<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles\Index;

use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;
use Tests\Fixtures\Support\Schemas\ApiVersion;
use Workbench\App\Entities\Articles\Article;
use Workbench\App\Http\Api\V1;

    final class Controller
    {
        #[Route(
            name: 'api.v1.articles.index',
            uri: 'api/v1/articles',
            methods: Method::Get,
        )]
        public function __invoke(Authorizer $authorizer, Validator $validator): V1\Articles\Articles
        {
            return Article::filter(
                $validator->filters
            )->sort(
                $validator->sort
            )->cursorPaginate()
            ->toSchemaCollection(ApiVersion::V1);
        }
    }
