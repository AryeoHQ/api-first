<?php

declare(strict_types=1);

namespace Support\Http\Api\Providers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionNamedType;
use Support\Http\Api\Console\Commands\MakeController\MakeController;
use Support\Http\Api\Console\Commands\MakeResource\Listeners\InjectSchemaProperties;
use Support\Http\Api\Console\Commands\MakeResource\MakeResource;
use Support\Http\Api\Request\TokenContext;
use Support\Http\Api\Resources\Json\Middleware\AppendFilters;
use Support\Http\Api\Resources\Json\Middleware\AppendSort;
use Support\Http\Api\Resources\Json\PaginatedResourceResponse\PagingInformation\PagingInformation;
use Support\Http\Requests\Contracts\CastableData;
use Support\Http\Resources\Schemas\Console\Commands\MakeResource\Events\BuildingSchema;
use Tooling\Http\Api\Composer\ClassMap\Collectors\ApiVersions;

class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerBindings();
        $this->registerMiddleware();
        $this->registerCursorResolver();
        $this->registerMixins();

        $this->app->tag([ApiVersions::class], 'tooling.classmap.collectors');
    }

    public function boot(): void
    {
        $this->bootListeners();
        $this->bootCommands();
    }

    private function registerMixins(): void
    {
        JsonResource::mixin(new PagingInformation);
        Request::mixin(new TokenContext);
    }

    private function registerCursorResolver(): void
    {
        CursorPaginator::currentCursorResolver(function () {
            return Cursor::fromEncoded(request()->input('paging.cursor'));
        });
    }

    private function registerBindings(): void
    {
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
    }

    private function registerMiddleware(): void
    {
        Route::middlewareGroup('api-first', [
            AppendFilters::class,
            AppendSort::class,
        ]);
    }

    private function bootListeners(): void
    {
        Event::listen(BuildingSchema::class, InjectSchemaProperties::class);
    }

    private function bootCommands(): void
    {
        $this->commands([
            MakeController::class,
        ]);

        $this->app->booted(function () {
            $this->commands([
                MakeResource::class,
            ]);
        });
    }
}
