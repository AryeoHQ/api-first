<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeController;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Routing\Console\ControllerMakeCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Support\Entities\Console\Concerns\RetrievesEntity;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Commands\MakeController\Concerns\GeneratesAction;
use Support\Http\Api\Console\Commands\MakeController\Concerns\GeneratesRest;
use Support\Http\Api\Console\Enums\ActionMethod;
use Support\Http\Api\Console\Enums\Endpoint;
use Support\Http\Api\Console\Enums\EndpointType;
use Support\Http\Api\Console\Enums\Scope;
use Support\Http\Api\References\Controller;
use Support\Http\Api\References\Route;
use Support\Http\Commands\MakeAuthorizer;
use Support\Http\Commands\MakeValidator;
use Support\Http\Resources\Schemas\Attributes\CollectedBy\CollectedBy;
use Support\Http\Resources\Schemas\Attributes\UseSchema\UseSchema;
use Support\Http\Resources\Schemas\Attributes\Version\Version;
use Support\Http\Resources\Schemas\Contracts;
use Support\Routing\Enums\Method;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tooling\GeneratorCommands\Concerns\CreatesColocatedTests;
use Tooling\GeneratorCommands\Concerns\GeneratorCommandCompatibility;
use Tooling\GeneratorCommands\Contracts\GeneratesFile;

use function Laravel\Prompts\select;

class MakeController extends ControllerMakeCommand implements GeneratesFile
{
    use CreatesColocatedTests;
    use GeneratesAction;
    use GeneratesRest;
    use GeneratorCommandCompatibility;

    /** @use RetrievesEntity<Entity> */
    use RetrievesEntity;

    protected $description = 'Create a new API controller endpoint.';

    protected $type = 'Controller';

    public protected(set) Controller $controller;

    protected Contracts\Version $version;

    /** @var Collection<int, class-string<Contracts\Schema>> */
    protected Collection $schemas;

    protected string $schemaReturnType;

    public Controller $reference {
        get => $this->controller;
    }

    public Stringable $nameInput {
        get => $this->controller->name;
    }

    public Stringable $entityInput {
        get => str($this->option('entity'));
    }

    public function handle()
    {
        $this->resolveEntity();

        if (! $this->resolveVersion()) {
            return (bool) self::FAILURE;
        }

        $this->resolveController();
        $this->resolveSchema();

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

        return (bool) self::SUCCESS;
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

        return str_replace([
            '{{ imports }}',
            '{{ parameters }}',
            '{{ returnType }}',
        ], [
            $this->buildControllerImports(),
            $this->buildControllerParameters(),
            $this->buildReturnType(),
        ], $stub);
    }

    private function buildControllerImports(): string
    {
        return collect([
            'use '.$this->controller->route->fqcn.';',
            'use '.Method::class.';',
            'use '.$this->schemaReturnType.';',
        ])->when(
            $this->controller->scope === Scope::Instance,
            fn ($imports) => $imports
                ->push('use '.$this->controller->entity->fqcn.';')
        )->sort()->values()->implode("\n");
    }

    private function buildReturnType(): string
    {
        return ': '.class_basename($this->schemaReturnType);
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
            apiVersion: $this->version->name,
            entity: $entity,
            endpointType: $endpointType,
            endpointName: $endpoint instanceof Endpoint ? $endpoint->value : $endpoint,
            actionMethod: $actionMethod,
            scope: $scope,
        );

        return Controller::make($route);
    }

    private function resolveVersion(): bool
    {
        /** @var class-string<Contracts\Version>|null $enumClass */
        $enumClass = config('api-resource-schema.version', null);

        if (! $enumClass) {
            $this->components->error('`Version` enum not configured.');

            return false;
        }

        $this->schemas = UseSchema::resolve($this->entity->fqcn->toString());

        $availableVersions = $this->schemas->mapWithKeys(
            fn (string $schema): array => [Version::resolve($schema)->value => $schema]
        );

        $allVersions = collect($enumClass::cases());

        if ($input = $this->option('api-version')) {
            $schema = $availableVersions->first(
                fn (string $schema, string $value): bool => $value === $input || Version::resolve($schema)->name === $input,
            );

            if (! $schema) {
                $this->components->error("No schema exists for version [{$input}]. Create one with `make:resource`.");

                return false;
            }

            $this->version = Version::resolve($schema);

            return true;
        }

        $options = $allVersions->mapWithKeys(fn (Contracts\Version $case) => [
            $case->value => $case->value,
        ]);

        $unavailableVersions = $options->keys()->diff($availableVersions->keys());

        if ($availableVersions->isEmpty()) {
            $this->components->error('No schemas found for ['.class_basename($this->entity->fqcn->toString()).'].');
            $this->components->bulletList([
                'Create a schema using `make:resource`.',
                'Add #['.class_basename(UseSchema::class).'(YourSchema::class)] to the entity model.',
            ]);

            return false;
        }

        $selected = select(
            label: 'Select a version.',
            options: $availableVersions->keys()->values()->toArray(),
            hint: $unavailableVersions->isNotEmpty()
                ? 'Missing schemas: '.$unavailableVersions->implode(', ')
                : '',
        );

        $this->version = Version::resolve($availableVersions->get($selected));

        return true;
    }

    private function resolveSchema(): void
    {
        $schema = $this->schemas->first(
            fn (string $schema): bool => Version::resolve($schema) === $this->version,
        );

        $this->schemaReturnType = match ($this->controller->scope) {
            Scope::Instance => $schema,
            Scope::Resource => CollectedBy::resolve($schema),
        };
    }

    protected function getArguments(): array
    {
        return [];
    }

    /** @return array<int, InputOption> */
    protected function getOptions(): array
    {
        return [
            new InputOption('api-version', null, InputOption::VALUE_OPTIONAL, 'The API version (case name or value).'),
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
