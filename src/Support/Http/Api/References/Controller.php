<?php

declare(strict_types=1);

namespace Support\Http\Api\References;

use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Enums\Scope;
use Support\Http\Commands\References\Authorizer;
use Support\Http\Commands\References\Validator;
use Tooling\GeneratorCommands\References\GenericClass;

final class Controller extends GenericClass
{
    public Route $route;

    public Stringable $stubPath {
        get => str(__DIR__.'/stubs/controller.stub');
    }

    public Entity $entity {
        get => $this->route->entity;
    }

    public Scope $scope {
        get => $this->route->scope;
    }

    public Authorizer $authorizer {
        get => resolve(Authorizer::class, [
            'name' => 'Authorizer',
            'baseNamespace' => $this->namespace,
        ]);
    }

    public Validator $validator {
        get => resolve(Validator::class, [
            'name' => 'Validator',
            'baseNamespace' => $this->namespace,
        ]);
    }

    public null|Stringable $subNamespace {
        get => str('Http\\Api')
            ->append('\\', $this->route->apiVersion->toString())
            ->append('\\', $this->route->entity->plural->toString())
            ->append('\\', Str::studly($this->route->endpointName->toString()));
    }

    public static function make(Route $route): self
    {
        return tap(
            resolve(self::class, ['name' => 'Controller', 'baseNamespace' => $route->entity->baseNamespace]),
            fn (self $controller) => $controller->route = $route
        );
    }
}
