<?php

declare(strict_types=1);

namespace Support\Http\Api\References;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Enums\ActionMethod;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\Console\Enums\Scope;
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
            Route::make('V1', $this->entity, EndpointType::Rest, 'index'),
        );

        $this->assertSame('\\Workbench\\App\\Http\\Api\\V1\\Orders\\Index', $controller->namespace->toString());
        $this->assertSame('\\Workbench\\App\\Http\\Api\\V1\\Orders\\Index\\Controller', $controller->fqcn->toString());
    }

    #[Test]
    public function it_resolves_namespace_for_action_endpoint(): void
    {
        $controller = Controller::make(
            Route::make('V1', $this->entity, EndpointType::Action, 'PayInvoice', ActionMethod::Post),
        );

        $this->assertSame('\\Workbench\\App\\Http\\Api\\V1\\Orders\\PayInvoice', $controller->namespace->toString());
        $this->assertSame('\\Workbench\\App\\Http\\Api\\V1\\Orders\\PayInvoice\\Controller', $controller->fqcn->toString());
    }

    #[Test]
    public function it_resolves_file_path(): void
    {
        $controller = Controller::make(
            Route::make('V1', $this->entity, EndpointType::Rest, 'index'),
        );

        $this->assertStringEndsWith('workbench/app/Http/Api/V1/Orders/Index/Controller.php', $controller->filePath->toString());
    }

    #[Test]
    public function it_resolves_directory(): void
    {
        $controller = Controller::make(
            Route::make('V1', $this->entity, EndpointType::Rest, 'index'),
        );

        $this->assertStringEndsWith('workbench/app/Http/Api/V1/Orders/Index', $controller->directory->toString());
    }

    #[Test]
    public function it_exposes_scope(): void
    {
        $show = Controller::make(Route::make('V1', $this->entity, EndpointType::Rest, 'show', scope: Scope::Instance));
        $index = Controller::make(Route::make('V1', $this->entity, EndpointType::Rest, 'index', scope: Scope::Resource));
        $instanceAction = Controller::make(Route::make('V1', $this->entity, EndpointType::Action, 'Cancel', scope: Scope::Instance));
        $resourceAction = Controller::make(Route::make('V1', $this->entity, EndpointType::Action, 'Export', scope: Scope::Resource));

        $this->assertSame(Scope::Instance, $show->scope);
        $this->assertSame(Scope::Resource, $index->scope);
        $this->assertSame(Scope::Instance, $instanceAction->scope);
        $this->assertSame(Scope::Resource, $resourceAction->scope);
    }

    #[Test]
    public function it_provides_route_companion(): void
    {
        $controller = Controller::make(
            Route::make('V1', $this->entity, EndpointType::Rest, 'index'),
        );

        $this->assertSame('\\Support\\Routing\\Attributes\\Route', $controller->route->fqcn->toString());
    }

    #[Test]
    public function it_provides_test_companion(): void
    {
        $controller = Controller::make(
            Route::make('V1', $this->entity, EndpointType::Rest, 'index'),
        );

        $this->assertSame(
            '\\Workbench\\App\\Http\\Api\\V1\\Orders\\Index\\ControllerTest',
            $controller->test->fqcn->toString()
        );
        $this->assertStringEndsWith(
            'workbench/app/Http/Api/V1/Orders/Index/ControllerTest.php',
            $controller->test->filePath->toString()
        );
    }

    #[Test]
    public function it_provides_authorizer_companion(): void
    {
        $controller = Controller::make(
            Route::make('V1', $this->entity, EndpointType::Rest, 'index'),
        );

        $this->assertSame(
            '\\Workbench\\App\\Http\\Api\\V1\\Orders\\Index\\Authorizer',
            $controller->authorizer->fqcn->toString()
        );
        $this->assertStringEndsWith(
            'workbench/app/Http/Api/V1/Orders/Index/Authorizer.php',
            $controller->authorizer->filePath->toString()
        );
    }

    #[Test]
    public function it_provides_validator_companion(): void
    {
        $controller = Controller::make(
            Route::make('V1', $this->entity, EndpointType::Rest, 'index'),
        );

        $this->assertSame(
            '\\Workbench\\App\\Http\\Api\\V1\\Orders\\Index\\Validator',
            $controller->validator->fqcn->toString()
        );
        $this->assertStringEndsWith(
            'workbench/app/Http/Api/V1/Orders/Index/Validator.php',
            $controller->validator->filePath->toString()
        );
    }
}
