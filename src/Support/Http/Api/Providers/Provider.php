<?php

declare(strict_types=1);

namespace Support\Http\Api\Providers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionNamedType;
use Support\Http\Api\Console\Commands\MakeController\MakeController;
use Support\Http\Api\Console\Commands\MakeResource\MakeResource;
use Support\Http\Api\Request\TokenContext;
use Support\Http\Api\Resources\Json\Middleware\AppendFilters;
use Support\Http\Api\Resources\Json\Middleware\AppendSort;
use Support\Http\Api\Resources\Json\PaginatedResourceResponse\PagingInformation\PagingInformation;
use Support\Http\Requests\Contracts\CastableData;

class Provider extends ServiceProvider
{
    public function register(): void
    {
        JsonResource::mixin(new PagingInformation);
        Request::mixin(new TokenContext);

        CursorPaginator::currentCursorResolver(function () {
            return Cursor::fromEncoded(request()->input('paging.cursor'));
        });

        $this->app->scoped(CastableData::class, function (): null|CastableData {
            $route = request()->route();

            if ($route === null) {
                return null;
            }

            foreach ($route->signatureParameters(['subClass' => CastableData::class]) as $parameter) {
                $type = $parameter->getType();

                if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    continue;
                }

                return resolve($type->getName());
            }

            return null;
        });

        Route::middlewareGroup('api-first', [
            AppendFilters::class,
            AppendSort::class,
        ]);
    }

    public function boot(): void
    {
        $this->commands([
            MakeController::class,
            MakeResource::class,
        ]);
    }
}
