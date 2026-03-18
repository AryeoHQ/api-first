<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Routing\Console\ControllerMakeCommand;
use Illuminate\Support\Stringable;
use Support\Entities\Console\Concerns\RetrievesEntity;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Concerns\ResolvesApiVersion;
use Support\Http\Api\Console\Enums\ActionMethod;
use Support\Http\Api\Console\Enums\Endpoints;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\References\Controller;
use Support\Http\Commands\MakeAuthorizer;
use Support\Http\Commands\MakeValidator;
use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tooling\GeneratorCommands\Concerns\CreatesColocatedTests;
use Tooling\GeneratorCommands\Concerns\GeneratorCommandCompatibility;
use Tooling\GeneratorCommands\Concerns\SearchesClasses;
use Tooling\GeneratorCommands\Contracts\GeneratesFile;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class MakeController extends ControllerMakeCommand implements GeneratesFile
{
    use CreatesColocatedTests;
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

    /** @var array<int, Controller> */
    private array $controllers = [];

    public function handle()
    {
        $this->resolveApiVersion();
        $this->resolveEntity();
        $this->resolveControllers();

        foreach ($this->controllers as $controller) {
            $this->controller = $controller;

            // Does not call parent::handle() to skip base command's operations
            GeneratorCommand::handle();

            if ($this->option('authorizer')) {
                $this->call(MakeAuthorizer::class, [
                    'name' => 'Authorizer',
                    '--namespace' => $controller->namespace->ltrim('\\')->toString(),
                    '--force' => $this->option('force'),
                ]);
            }

            if ($this->option('validator')) {
                $this->call(MakeValidator::class, [
                    'name' => 'Validator',
                    '--namespace' => $controller->namespace->ltrim('\\')->toString(),
                    '--force' => $this->option('force'),
                ]);
            }
        }

        return self::SUCCESS; // @phpstan-ignore return.type
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
            $this->controller->routeName->toString(),
            $this->controller->uri->toString(),
            $this->controller->httpMethod->toString(),
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
            'use '.Route::class.';',
            'use '.Method::class.';',
        ])->when(
            $this->controller->isSingleResource,
            fn ($imports) => $imports
                ->push('use '.$this->controller->entity->fqcn.';')
        )->sort()->values()->implode("\n");
    }

    private function buildControllerParameters(): string
    {
        $usesAuthorizerOrValidator = $this->option('authorizer') || $this->option('validator');

        return collect()
            ->when($this->option('authorizer'), fn ($p) => $p->push('Authorizer $authorizer'))
            ->when($this->option('validator'), fn ($p) => $p->push('Validator $validator'))
            ->when(! $usesAuthorizerOrValidator, fn ($p) => $p->push('Request $request'))
            ->when(
                $this->controller->isSingleResource,
                fn ($parameters) => $parameters
                    ->push($this->controller->modelBinding->toString())
            )->implode(', ');
    }

    private function resolveControllers(): void
    {
        $endpointType = $this->resolveEndpointType();

        if ($endpointType === EndpointType::Action) {
            $this->resolveActionController($endpointType);

            return;
        }

        $this->resolveRestControllers($endpointType);
    }

    private function resolveEndpointType(): EndpointType
    {
        $typeOption = $this->option('type');

        if ($typeOption !== null) {
            return EndpointType::from($typeOption);
        }

        return EndpointType::from(select(
            label: 'What type of endpoint would you like to create?',
            options: array_column(EndpointType::cases(), 'value'),
            required: true,
        ));
    }

    private function resolveRestControllers(EndpointType $endpointType): void
    {
        $endpointOption = $this->option('endpoint');

        if ($endpointOption !== null) {
            $this->controllers = [
                $this->buildController($this->entity, $endpointType, $endpointOption),
            ];

            return;
        }

        $selected = multiselect(
            label: 'What endpoints would you like to create?',
            options: array_column(Endpoints::cases(), 'value'),
            scroll: count(Endpoints::cases()),
            required: true,
        );

        $this->controllers = array_map(
            fn (string $name): Controller => $this->buildController($this->entity, $endpointType, $name),
            $selected,
        );
    }

    private function resolveActionController(EndpointType $endpointType): void
    {
        $actionName = $this->option('action') ?? text(
            label: 'What is the name of the action? (ie: PayInvoice, Download, etc.)',
            required: true,
        );

        $actionMethod = $this->option('action-method')
            ? ActionMethod::from($this->option('action-method'))
            : ActionMethod::Post;

        $this->controllers = [
            $this->buildController($this->entity, $endpointType, $actionName, $actionMethod),
        ];
    }

    private function buildController(
        Entity $entity,
        EndpointType $endpointType,
        string $endpointName,
        ActionMethod $actionMethod = ActionMethod::Post,
    ): Controller {
        return Controller::make(
            apiVersion: $this->apiVersion,
            entity: $entity,
            endpointType: $endpointType,
            endpointName: $endpointName,
            actionMethod: $actionMethod,
        );
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
            new InputOption('endpoint', null, InputOption::VALUE_OPTIONAL, 'The endpoint name (e.g. index, store).'),
            new InputOption('action', null, InputOption::VALUE_OPTIONAL, 'The action name (e.g. PayInvoice).'),
            new InputOption('action-method', null, InputOption::VALUE_OPTIONAL, 'The action HTTP method (GET or POST).'),
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
