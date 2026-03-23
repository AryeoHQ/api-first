<?php

declare(strict_types=1);

namespace Support\Http\Api\References;

use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Enums\ActionMethod;
use Support\Http\Api\Console\Enums\Endpoint;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\Console\Enums\Scope;
use Support\Routing\Enums\Method;
use Tooling\GeneratorCommands\References\GenericClass;

final class Route extends GenericClass
{
    public Stringable $apiVersion;

    public Entity $entity;

    public EndpointType $endpointType;

    public Stringable $endpointName;

    public ActionMethod $actionMethod;

    public Scope $scope;

    public static function make(Stringable|string $apiVersion, Entity $entity, EndpointType $endpointType, Stringable|string $endpointName, ActionMethod $actionMethod = ActionMethod::Post, Scope $scope = Scope::Instance): self
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
        $route->scope = $scope;

        return $route;
    }

    public Stringable $routeName {
        get {
            $base = str('api.')->append($this->apiVersion->toString())->append('.', $this->entity->plural->lower()->toString());

            return match ($this->endpointType) {
                EndpointType::Action => $base->append('.actions.', Str::kebab($this->endpointName->toString())),
                EndpointType::Rest => $base->append('.', $this->endpointName->lower()->toString()),
            };
        }
    }

    public Stringable $uri {
        get {
            $base = str('api/')->append($this->apiVersion->lower()->toString())->append('/', $this->entity->plural->lower()->toString());

            return match ($this->endpointType) {
                EndpointType::Action => match ($this->scope) {
                    Scope::Instance => $base->append('/{', $this->entity->variableName->toString(), '}/actions/', Str::kebab($this->endpointName->toString())),
                    Scope::Resource => $base->append('/actions/', Str::kebab($this->endpointName->toString())),
                },
                EndpointType::Rest => match ($this->scope) {
                    Scope::Instance => $base->append('/{', $this->entity->variableName->toString(), '}'),
                    Scope::Resource => $base,
                },
            };
        }
    }

    public Method $method {
        get => match ($this->endpointType) {
            EndpointType::Action => Method::from($this->actionMethod->value),
            EndpointType::Rest => Endpoint::from($this->endpointName->lower()->toString())->method(),
        };
    }
}
