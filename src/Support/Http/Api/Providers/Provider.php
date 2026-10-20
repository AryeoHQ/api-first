<?php

declare(strict_types=1);

namespace Support\Http\Api\Providers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use ReflectionNamedType;
use Support\Http\Api\Console\Commands\MakeController\MakeController;
use Support\Http\Api\Console\Commands\MakeResource\Listeners\InjectSchemaProperties;
use Support\Http\Api\Console\Commands\MakeResource\MakeResource;
use Support\Http\Api\Errors\Renderer;
use Support\Http\Api\Request\TokenContext;
use Support\Http\Requests\Contracts\CastableData;
use Support\Http\Resources\Schemas\Console\Commands\MakeResource\Events\BuildingSchema;

class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
        $this->registerBindings();
        $this->registerCursorResolver();
        $this->registerMixins();
        $this->registerErrorRenderer();
    }

    public function boot(): void
    {
        $this->bootPublishables();
        $this->bootListeners();
        $this->bootCommands();
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../../config/api-first.php', 'api-first');
    }

    private function registerErrorRenderer(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if ($handler instanceof Handler) {
                $handler->renderable(new Renderer);
            }
        });
    }

    private function bootPublishables(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../../../../config/api-first.php' => config_path('api-first.php'),
            ], ['config', 'api-first', 'api-first:config']);
        }
    }

    private function registerMixins(): void
    {
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
