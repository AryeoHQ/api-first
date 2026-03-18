<?php

declare(strict_types=1);

namespace Support\Http\Api\References;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Enums\ActionMethod;
use Support\Http\Api\Console\Enums\EndpointType;
use Tests\TestCase;

#[CoversClass(Controller::class)]
final class ControllerTest extends TestCase
{
    private Entity $entity {
        get => new Entity(name: 'Order', baseNamespace: 'Workbench\\App\\');
    }

    #[Test]
    public function it_resolves_namespace_for_rest_endpoint(): void
    {
        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Rest,
            endpointName: 'index',
        );

        $this->assertSame('\\Workbench\\App\\Http\\Api\\V1\\Orders\\Index', $controller->namespace->toString());
        $this->assertSame('\\Workbench\\App\\Http\\Api\\V1\\Orders\\Index\\Controller', $controller->fqcn->toString());
    }

    #[Test]
    public function it_resolves_namespace_for_action_endpoint(): void
    {
        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Action,
            endpointName: 'PayInvoice',
            actionMethod: ActionMethod::Post,
        );

        $this->assertSame('\\Workbench\\App\\Http\\Api\\V1\\Orders\\Actions\\PayInvoice', $controller->namespace->toString());
        $this->assertSame('\\Workbench\\App\\Http\\Api\\V1\\Orders\\Actions\\PayInvoice\\Controller', $controller->fqcn->toString());
    }

    #[Test]
    public function it_resolves_file_path(): void
    {
        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Rest,
            endpointName: 'index',
        );

        $this->assertStringEndsWith('workbench/app/Http/Api/V1/Orders/Index/Controller.php', $controller->filePath->toString());
    }

    #[Test]
    public function it_resolves_directory(): void
    {
        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Rest,
            endpointName: 'index',
        );

        $this->assertStringEndsWith('workbench/app/Http/Api/V1/Orders/Index', $controller->directory->toString());
    }

    #[Test]
    public function it_resolves_route_name_for_rest(): void
    {
        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Rest,
            endpointName: 'index',
        );

        $this->assertSame('api.V1.orders.index', $controller->routeName->toString());
    }

    #[Test]
    public function it_resolves_route_name_for_action(): void
    {
        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Action,
            endpointName: 'PayInvoice',
        );

        $this->assertSame('api.V1.orders.actions.pay-invoice', $controller->routeName->toString());
    }

    #[Test]
    public function it_resolves_uri_for_rest_collection(): void
    {
        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Rest,
            endpointName: 'index',
        );

        $this->assertSame('api/v1/orders', $controller->uri->toString());
    }

    #[Test]
    public function it_resolves_uri_for_rest_single_resource(): void
    {
        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Rest,
            endpointName: 'show',
        );

        $this->assertSame('api/v1/orders/{order}', $controller->uri->toString());
    }

    #[Test]
    public function it_resolves_uri_for_action(): void
    {
        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Action,
            endpointName: 'PayInvoice',
        );

        $this->assertSame('api/v1/orders/{order}/actions/pay-invoice', $controller->uri->toString());
    }

    #[Test]
    public function it_resolves_http_method_for_rest(): void
    {
        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Rest,
            endpointName: 'index',
        );

        $this->assertSame('Method::Get', $controller->httpMethod->toString());
    }

    #[Test]
    public function it_resolves_http_method_for_action(): void
    {
        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Action,
            endpointName: 'PayInvoice',
            actionMethod: ActionMethod::Get,
        );

        $this->assertSame('Method::Get', $controller->httpMethod->toString());
    }

    #[Test]
    public function it_identifies_single_resource_endpoints(): void
    {
        $show = Controller::make('V1', $this->entity, EndpointType::Rest, 'show');
        $index = Controller::make('V1', $this->entity, EndpointType::Rest, 'index');
        $action = Controller::make('V1', $this->entity, EndpointType::Action, 'Cancel');

        $this->assertTrue($show->isSingleResource);
        $this->assertFalse($index->isSingleResource);
        $this->assertTrue($action->isSingleResource);
    }

    #[Test]
    public function it_resolves_model_binding(): void
    {
        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Rest,
            endpointName: 'show',
        );

        $this->assertSame('Order $order', $controller->modelBinding->toString());
    }

    #[Test]
    public function it_provides_test_companion(): void
    {
        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Rest,
            endpointName: 'index',
        );

        $this->assertSame('\\Workbench\\App\\Http\\Api\\V1\\Orders\\Index\\ControllerTest', $controller->test->fqcn->toString());
        $this->assertStringEndsWith('workbench/app/Http/Api/V1/Orders/Index/ControllerTest.php', $controller->test->filePath->toString());
    }

    #[Test]
    public function it_provides_authorizer_companion(): void
    {
        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Rest,
            endpointName: 'index',
        );

        $this->assertSame('\\Workbench\\App\\Http\\Api\\V1\\Orders\\Index\\Authorizer', $controller->authorizer->fqcn->toString());
        $this->assertStringEndsWith('workbench/app/Http/Api/V1/Orders/Index/Authorizer.php', $controller->authorizer->filePath->toString());
    }

    #[Test]
    public function it_provides_validator_companion(): void
    {
        $controller = Controller::make(
            apiVersion: 'V1',
            entity: $this->entity,
            endpointType: EndpointType::Rest,
            endpointName: 'index',
        );

        $this->assertSame('\\Workbench\\App\\Http\\Api\\V1\\Orders\\Index\\Validator', $controller->validator->fqcn->toString());
        $this->assertStringEndsWith('workbench/app/Http/Api/V1/Orders/Index/Validator.php', $controller->validator->filePath->toString());
    }
}
