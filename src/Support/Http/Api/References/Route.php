<?php

declare(strict_types=1);

namespace Support\Http\Api\References;

use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Enums\ActionMethod;
use Support\Http\Api\Console\Enums\Endpoints;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Routing\Enums\Method;
use Tooling\GeneratorCommands\References\GenericClass;

final class Route extends GenericClass
{
    public Stringable $apiVersion;

    public Entity $entity;

    public EndpointType $endpointType;

    public Stringable $endpointName;

    public ActionMethod $actionMethod;

    public static function make(Stringable|string $apiVersion, Entity $entity, EndpointType $endpointType, Stringable|string $endpointName, ActionMethod $actionMethod = ActionMethod::Post): self
    {
        $route = new self(
            name: 'Route',
            baseNamespace: 'Support\\Routing\\Attributes',
        );

        $route->apiVersion = str($apiVersion);
        $route->entity = $entity;
        $route->endpointType = $endpointType;
        $route->endpointName = str($endpointName);
        $route->actionMethod = $actionMethod;

        return $route;
    }

    public Stringable $routeName {
        get {
            $base = str('api.')
                ->append($this->apiVersion->toString())
                ->append('.', $this->entity->plural->lower()->toString());

            if ($this->endpointType === EndpointType::Action) {
                return $base->append('.actions.', Str::kebab($this->endpointName->toString()));
            }

            return $base->append('.', $this->endpointName->lower()->toString());
        }
    }

    public Stringable $uri {
        get {
            $base = str('api/')
                ->append($this->apiVersion->lower()->toString())
                ->append('/', $this->entity->plural->lower()->toString());

            if ($this->endpointType === EndpointType::Action) {
                return $base->append('/{', $this->entity->variableName->toString(), '}/actions/', Str::kebab($this->endpointName->toString()));
            }

            $endpoint = Endpoints::tryFrom($this->endpointName->lower()->toString());

            if ($endpoint?->isSingleResource()) {
                return $base->append('/{', $this->entity->variableName->toString(), '}');
            }

            return $base;
        }
    }

    public Method $method {
        get {
            if ($this->endpointType === EndpointType::Action) {
                return Method::from($this->actionMethod->value);
            }

            return Endpoints::from($this->endpointName->lower()->toString())->httpMethod();
        }
    }
}
