<?php

declare(strict_types=1);

namespace Support\Http\Api\References;

use Illuminate\Support\Str;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Enums\Endpoints;
use Support\Http\Api\Console\Enums\EndpointType;
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
        $subdirectory = $route->endpointType === EndpointType::Action
            ? str('Actions')->append('\\', Str::studly($route->endpointName->toString()))
            : $route->endpointName->ucfirst();

        $namespace = $route->entity->baseNamespace
            ->append('\\Http\\Api\\')
            ->append($route->apiVersion->toString())
            ->append('\\', $route->entity->plural->toString())
            ->append('\\', $subdirectory->toString());

        $controller = new self(name: 'Controller', baseNamespace: $namespace);
        $controller->route = $route;

        return $controller;
    }

    public bool $isSingleResource {
        get {
            if ($this->route->endpointType === EndpointType::Action) {
                return true;
            }

            $endpoint = Endpoints::tryFrom($this->route->endpointName->lower()->toString());

            return $endpoint?->isSingleResource() ?? false;
        }
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
