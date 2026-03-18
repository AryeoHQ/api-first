<?php

declare(strict_types=1);

namespace Support\Http\Api\Providers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use ReflectionNamedType;
use Support\Http\Api\Console\Commands\MakeController;
use Support\Http\Api\Request\TokenContext;
use Support\Http\Api\Resources\Json\PaginatedResourceResponse\PagingInformation\PagingInformation;
use Support\Http\Requests\Contracts\CastableData;

class Provider extends ServiceProvider
{
    public function register(): void
    {
        JsonResource::mixin(new PagingInformation);
        Request::mixin(new TokenContext);

        $this->app->scoped(CastableData::class, function ($app): null|CastableData {
            $route = $app['request']->route();

            if ($route === null) {
                return null;
            }

            foreach ($route->signatureParameters(['subClass' => CastableData::class]) as $parameter) {
                $type = $parameter->getType();

                if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    continue;
                }

                return $app->make($type->getName());
            }

            return null;
        });

        $this->commands([
            MakeController::class,
        ]);
    }
}
