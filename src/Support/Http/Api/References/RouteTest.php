<?php

declare(strict_types=1);

namespace Support\Http\Api\References;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Enums\ActionMethod;
use Support\Http\Api\Console\Enums\Endpoint;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\Console\Enums\Scope;
use Support\Routing\Enums\Method;
use Tests\TestCase;

#[CoversClass(Route::class)]
final class RouteTest extends TestCase
{
    private Entity $entity {
        get => new Entity(name: 'Order', baseNamespace: 'Workbench\\App\\');
    }

    #[Test]
    public function it_references_the_route_attribute_class(): void
    {
        $route = $this->makeRoute(EndpointType::Rest, Endpoint::Index->value);

        $this->assertSame('\\Support\\Routing\\Attributes\\Route', $route->fqcn->toString());
    }

    #[Test]
    public function it_resolves_route_name_for_rest(): void
    {
        $route = $this->makeRoute(EndpointType::Rest, Endpoint::Index->value);

        $this->assertSame('api.V1.orders.index', $route->routeName->toString());
    }

    #[Test]
    public function it_resolves_route_name_for_action(): void
    {
        $route = $this->makeRoute(EndpointType::Action, 'PayInvoice');

        $this->assertSame('api.V1.orders.actions.pay-invoice', $route->routeName->toString());
    }

    #[Test]
    public function it_resolves_uri_for_rest_collection(): void
    {
        $route = $this->makeRoute(EndpointType::Rest, Endpoint::Index->value, scope: Scope::Resource);

        $this->assertSame('api/v1/orders', $route->uri->toString());
    }

    #[Test]
    public function it_resolves_uri_for_rest_single_resource(): void
    {
        $route = $this->makeRoute(EndpointType::Rest, Endpoint::Show->value);

        $this->assertSame('api/v1/orders/{order}', $route->uri->toString());
    }

    #[Test]
    public function it_resolves_uri_for_action(): void
    {
        $route = $this->makeRoute(EndpointType::Action, 'PayInvoice');

        $this->assertSame('api/v1/orders/{order}/actions/pay-invoice', $route->uri->toString());
    }

    #[Test]
    public function it_resolves_uri_for_collection_scoped_action(): void
    {
        $route = $this->makeRoute(EndpointType::Action, 'ExportOrders', scope: Scope::Resource);

        $this->assertSame('api/v1/orders/actions/export-orders', $route->uri->toString());
    }

    #[Test]
    public function it_resolves_method_for_rest(): void
    {
        $this->assertSame(Method::Get, $this->makeRoute(EndpointType::Rest, Endpoint::Index->value)->method);
        $this->assertSame(Method::Post, $this->makeRoute(EndpointType::Rest, Endpoint::Store->value)->method);
        $this->assertSame(Method::Get, $this->makeRoute(EndpointType::Rest, Endpoint::Show->value)->method);
        $this->assertSame(Method::Patch, $this->makeRoute(EndpointType::Rest, Endpoint::Update->value)->method);
        $this->assertSame(Method::Delete, $this->makeRoute(EndpointType::Rest, Endpoint::Delete->value)->method);
        $this->assertSame(Method::Post, $this->makeRoute(EndpointType::Rest, Endpoint::Search->value)->method);
    }

    #[Test]
    public function it_resolves_method_for_action(): void
    {
        $post = $this->makeRoute(EndpointType::Action, 'PayInvoice', ActionMethod::Post);

        $this->assertSame(Method::Post, $post->method);
    }

    private function makeRoute(EndpointType $endpointType, string $endpointName, ActionMethod $actionMethod = ActionMethod::Post, Scope $scope = Scope::Instance): Route
    {
        return Route::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: $endpointType,
            endpointName: $endpointName,
            actionMethod: $actionMethod,
            scope: $scope,
        );
    }
}
