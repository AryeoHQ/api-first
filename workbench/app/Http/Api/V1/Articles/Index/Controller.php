<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles\Index;

use Support\Routing\Enums\Method;
use \Support\Routing\Attributes\Route;

final class Controller
{
    #[Route(
        name: 'api.v1.articles.index',
        uri: 'api/v1/articles',
        methods: Method::Get,
    )]
    public function __invoke(Authorizer $authorizer, Validator $validator)
    {
        //
    }
}
