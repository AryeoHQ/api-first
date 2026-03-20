<?php

declare(strict_types=1);

namespace Support\Http\Api\References;

use Illuminate\Support\Str;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Enums\Scope;
use Support\Http\Commands\References\Authorizer;
use Support\Http\Commands\References\Validator;
use Tooling\GeneratorCommands\References\GenericClass;

final class Controller extends GenericClass
{
    public Route $route;

    public Entity $entity {
        get => $this->route->entity;
    }

    public static function make(Route $route): self
    {
        $namespace = $route->entity->baseNamespace
            ->append('\\Http\\Api\\')
            ->append($route->apiVersion->toString())
            ->append('\\', $route->entity->plural->toString())
            ->append('\\', Str::studly($route->endpointName->toString()));

        $controller = new self(name: 'Controller', baseNamespace: $namespace);
        $controller->route = $route;

        return $controller;
    }

    public Scope $scope {
        get => $this->route->scope;
    }

    public Authorizer $authorizer {
        get => new Authorizer(
            name: 'Authorizer',
            baseNamespace: $this->namespace,
        );
    }

    public Validator $validator {
        get => new Validator(
            name: 'Validator',
            baseNamespace: $this->namespace,
        );
    }
}
