<?php

declare(strict_types=1);

namespace Support\Http\Api\References;

use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Enums\ActionMethod;
use Support\Http\Api\Console\Enums\Endpoints;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Commands\References\Authorizer;
use Support\Http\Commands\References\Validator;
use Tooling\GeneratorCommands\References\GenericClass;

final class Controller extends GenericClass
{
    public Stringable $apiVersion;

    public Entity $entity;

    public EndpointType $endpointType;

    public Stringable $endpointName;

    public ActionMethod $actionMethod;

    public static function make(
        Stringable|string $apiVersion,
        Entity $entity,
        EndpointType $endpointType,
        Stringable|string $endpointName,
        ActionMethod $actionMethod = ActionMethod::Post,
    ): self {
        $apiVersion = str($apiVersion);
        $endpointName = str($endpointName);

        $subdirectory = $endpointType === EndpointType::Action
            ? str('Actions')->append('\\', Str::studly($endpointName->toString()))
            : $endpointName->ucfirst();

        $namespace = $entity->baseNamespace
            ->append('\\Http\\Api\\')
            ->append($apiVersion->toString())
            ->append('\\', $entity->plural->toString())
            ->append('\\', $subdirectory->toString());

        $controller = new self(name: 'Controller', baseNamespace: $namespace);
        $controller->apiVersion = $apiVersion;
        $controller->entity = $entity;
        $controller->endpointType = $endpointType;
        $controller->endpointName = $endpointName;
        $controller->actionMethod = $actionMethod;

        return $controller;
    }

    public bool $isSingleResource {
        get {
            if ($this->endpointType === EndpointType::Action) {
                return true;
            }

            $endpoint = Endpoints::tryFrom($this->endpointName->lower()->toString());

            return $endpoint?->isSingleResource() ?? false;
        }
    }

    public Route $route {
        get => Route::make($this);
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
