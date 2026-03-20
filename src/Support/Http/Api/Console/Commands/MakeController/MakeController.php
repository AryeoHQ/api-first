<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Routing\Console\ControllerMakeCommand;
use Illuminate\Support\Stringable;
use Support\Entities\Console\Concerns\RetrievesEntity;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Commands\MakeController\Concerns\GeneratesAction;
use Support\Http\Api\Console\Commands\MakeController\Concerns\GeneratesRest;
use Support\Http\Api\Console\Commands\MakeController\Concerns\ResolvesApiVersion;
use Support\Http\Api\Console\Enums\ActionMethod;
use Support\Http\Api\Console\Enums\Endpoint;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\Console\Enums\Scope;
use Support\Http\Api\References\Controller;
use Support\Http\Api\References\Route;
use Support\Http\Commands\MakeAuthorizer;
use Support\Http\Commands\MakeValidator;
use Support\Routing\Enums\Method;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tooling\GeneratorCommands\Concerns\CreatesColocatedTests;
use Tooling\GeneratorCommands\Concerns\GeneratorCommandCompatibility;
use Tooling\GeneratorCommands\Concerns\SearchesClasses;
use Tooling\GeneratorCommands\Contracts\GeneratesFile;

class MakeController extends ControllerMakeCommand implements GeneratesFile
{
    use CreatesColocatedTests;
    use GeneratesAction;
    use GeneratesRest;
    use GeneratorCommandCompatibility;
    use ResolvesApiVersion;

    /** @use RetrievesEntity<Entity> */
    use RetrievesEntity;

    use SearchesClasses;

    protected $description = 'Create a new API controller endpoint.';

    protected $type = 'Controller';

    public protected(set) Controller $controller;

    public Controller $reference {
        get => $this->controller;
    }

    public string $stub = __DIR__.'/stubs/controller.stub';

    public Stringable $nameInput {
        get => $this->controller->name;
    }

    public Stringable $entityInput {
        get => str($this->option('entity'));
    }

    public function handle()
    {
        $this->resolveApiVersion();
        $this->resolveEntity();
        $this->resolveController();

        // Does not call parent::handle() to skip base command's operations
        GeneratorCommand::handle();

        if ($this->option('authorizer')) {
            $this->call(MakeAuthorizer::class, [
                'name' => 'Authorizer',
                '--namespace' => $this->controller->namespace->ltrim('\\')->toString(),
                '--force' => $this->option('force'),
            ]);
        }

        if ($this->option('validator')) {
            $this->call(MakeValidator::class, [
                'name' => 'Validator',
                '--namespace' => $this->controller->namespace->ltrim('\\')->toString(),
                '--force' => $this->option('force'),
            ]);
        }

        return self::SUCCESS;
    }

    protected function buildClass($name)
    {
        // Does not call parent::buildClass() to skip base command's operations
        $stub = GeneratorCommand::buildClass($name);

        $stub = str_replace([
            '{{ routeName }}',
            '{{ routeUri }}',
            '{{ routeMethod }}',
        ], [
            $this->controller->route->routeName->toString(),
            $this->controller->route->uri->toString(),
            'Method::'.$this->controller->route->method->name,
        ], $stub);

        $imports = $this->buildControllerImports();
        $parameters = $this->buildControllerParameters();

        return str_replace([
            '{{ imports }}',
            '{{ parameters }}',
        ], [
            $imports,
            $parameters,
        ], $stub);
    }

    private function buildControllerImports(): string
    {
        return collect([
            'use '.$this->controller->route->fqcn.';',
            'use '.Method::class.';',
        ])->when(
            $this->controller->scope === Scope::Instance,
            fn ($imports) => $imports
                ->push('use '.$this->controller->entity->fqcn.';')
        )->sort()->values()->implode("\n");
    }

    private function buildControllerParameters(): string
    {
        return collect()
            ->when($this->option('authorizer'), fn ($p) => $p->push('Authorizer $authorizer'))
            ->when($this->option('validator'), fn ($p) => $p->push('Validator $validator'))
            ->when(
                $this->controller->scope === Scope::Instance,
                fn ($parameters) => $parameters
                    ->push($this->controller->entity->name.' $'.$this->controller->entity->variableName)
            )->implode(', ');
    }

    private function resolveController(): void
    {
        $endpointType = $this->resolveEndpointType();

        $this->controller = match ($endpointType) {
            EndpointType::Action => $this->resolveActionController($endpointType),
            EndpointType::Rest => $this->resolveRestController($endpointType),
        };
    }

    private function resolveEndpointType(): EndpointType
    {
        return rescue(
            fn () => EndpointType::from($this->option('type')),
            fn () => EndpointType::from(
                \Laravel\Prompts\select(
                    label: 'What type of endpoint would you like to create?',
                    options: array_column(EndpointType::cases(), 'value'),
                    required: true,
                )
            ),
            false,
        );
    }

    private function buildController(Entity $entity, EndpointType $endpointType, Endpoint|Stringable $endpoint, ActionMethod $actionMethod = ActionMethod::Post, Scope $scope = Scope::Instance): Controller
    {
        $route = Route::make(
            apiVersion: $this->apiVersion,
            entity: $entity,
            endpointType: $endpointType,
            endpointName: $endpoint instanceof Endpoint ? $endpoint->value : $endpoint,
            actionMethod: $actionMethod,
            scope: $scope,
        );

        return Controller::make($route);
    }

    protected function getArguments(): array
    {
        return [];
    }

    /** @return array<int, InputOption> */
    protected function getOptions(): array
    {
        return [
            ...$this->getApiVersionInputOptions(),
            new InputOption('entity', null, InputOption::VALUE_OPTIONAL, 'The entity FQCN (e.g. App\\Entities\\Posts\\Post).'),
            new InputOption('type', null, InputOption::VALUE_OPTIONAL, 'The endpoint type (Rest or Action).'),
            new InputOption('authorizer', null, InputOption::VALUE_NEGATABLE, 'Generate an Authorizer class.', true),
            new InputOption('validator', null, InputOption::VALUE_NEGATABLE, 'Generate a Validator class.', true),
            new InputOption('force', null, InputOption::VALUE_NONE, 'Create the class even if it already exists.'),
        ];
    }

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        //
    }
}
