# Migration Plan: HTTP/API Code from `make-commands` → `api`

Extract all HTTP/API-related code (3 console commands, 4 stubs, 1 orchestration trait, 3 enums, 3 PHPStan rules) from `make-commands` into `api`, refactoring the command architecture to match the `entities` pattern — using `ResolvesModel`, Reference value objects, and `GeneratesForEntity` contracts instead of the legacy `WithDomainControllerContext` + `SearchesDomainModels` approach. This enables full deprecation of `make-commands` once remaining non-HTTP rules are rehomed. The `entities` package becomes a dependency of `api`.

---

## 1. Changes in `api`

### Dependencies

- [x] Add `aryeo/entities` as a dependency in `composer.json` — provides `ResolvesModel`, `ResolvesEntity`, `Model` references, and the `Entity` contract, replacing the legacy `SearchesDomainModels` trait

### Reference Value Objects

Following the pattern in `entities/src/Support/Entities/References/`, create:

- [x] `src/Support/Http/Api/References/Contracts/Endpoint.php` — interface extending `Tooling\GeneratorCommands\References\Contracts\Reference` with properties: `$apiVersion`, `$entity`, `$endpointType`, `$routeName`, `$uri`, `$httpMethod`
- [x] `src/Support/Http/Api/References/Concerns/AsEndpoint.php` — trait implementing the derivation logic currently in `WithDomainControllerContext` (namespace = `App\Http\Api\{V#}\{PluralModel}\{Endpoint}`, URI = `api/v#/plural-model/...`, route name = `api.V#.plural-model.endpoint`)
- [x] `src/Support/Http/Api/References/Concerns/RequiresEndpoint.php` — trait for sub-references that derive namespace/directory from an Endpoint (mirrors `RequiresEntity`)
- [x] `src/Support/Http/Api/References/Endpoint.php` — concrete `final class` using `AsEndpoint`, constructed from (apiVersion, entity, endpointType, endpointName); the Endpoint IS the Controller (analogous to how Entity IS the Model)
- [x] `src/Support/Http/Api/References/Request.php` — sub-reference using `RequiresEndpoint` (name = `Request`, subdirectory = null)
- [x] `src/Support/Http/Api/References/ControllerTest.php` — sub-reference using `RequiresEndpoint` (name = `ControllerTest`, subdirectory = null)
- [x] Co-locate `*Test.php` files alongside each Reference

### Enums

Copy from `make-commands`, updating namespaces:

- [x] `Support\Console\Enums\Endpoints` → `src/Support/Http/Api/Console/Enums/Endpoints.php`
- [x] `Support\Console\Enums\EndpointType` → `src/Support/Http/Api/Console/Enums/EndpointType.php`
- [x] `Support\Console\Enums\ActionMethods` → `src/Support/Http/Api/Console/Enums/ActionMethods.php`

### Console Contracts

Following the pattern in `entities/src/Support/Entities/Console/Contracts/`:

- [x] `src/Support/Http/Api/Console/Contracts/GeneratesEndpoint.php` — analogous to `GeneratesEntity`, contract for the `MakeController` orchestrator command; exposes `$entity` (Model reference from entities), `$apiVersion`, `$endpoints`
- [x] `src/Support/Http/Api/Console/Contracts/GeneratesForEndpoint.php` — contract for sub-commands (`MakeRequest`, `MakeTestForController`) that receive endpoint context

### Console Concerns

- [x] `src/Support/Http/Api/Console/Concerns/ResolvesApiVersion.php` — extract the API version prompting logic (scanning `app/Http/Api/V*`, offering "Create new") from the current `WithDomainControllerContext`
- [x] Model/entity resolution reuses `ResolvesEntity` from `aryeo/entities` directly — no custom trait needed

### Console Commands

- [x] `src/Support/Http/Api/Console/Commands/MakeController.php` — orchestrator command using `ResolvesEntity` (from entities), `ResolvesApiVersion`, `GeneratorCommandCompatibility`, `SearchesClasses`, `SearchesNamespaces`; builds `Endpoint` references, delegates to `MakeRequest` and `MakeTestForController`
- [x] `src/Support/Http/Api/Console/Commands/MakeRequest.php` — hidden command using `RetrievesEndpointFromOptions`, receives endpoint reference via options
- [x] `src/Support/Http/Api/Console/Commands/MakeTestForController.php` — hidden command using `RetrievesEndpointFromOptions`, receives endpoint reference via options
- [x] `src/Support/Http/Api/Console/Concerns/RetrievesEndpointFromOptions.php` — concern for sub-commands to reconstruct Endpoint from command options
- [x] Co-locate `*Test.php` files alongside each command

### Stubs

- [x] `src/Support/Http/Api/Console/Commands/stubs/controller.stub`
- [x] `src/Support/Http/Api/Console/Commands/stubs/controller.single-resource.stub`
- [x] `src/Support/Http/Api/Console/Commands/stubs/request.stub`
- [x] `src/Support/Http/Api/Console/Commands/stubs/controller-test.stub`
- [x] Update placeholder names in stubs to align with new Reference-driven replacements (`{{ namespace }}`, `{{ routeName }}`, `{{ routeUri }}`, `{{ routeMethod }}`, `{{ modelImport }}`, `{{ modelBinding }}`)

### PHPStan Rules

Move the 3 HTTP-related rules, modernizing to the entities-style `shouldHandle`/`handle` pattern with `#[NodeType]` attribute and `final class`:

- [x] `src/Tooling/Http/Api/PhpStan/ControllerIsFinal.php` — co-locate `ControllerIsFinalTest.php`
- [x] `src/Tooling/Http/Api/PhpStan/ControllerHasRouteAttribute.php` — co-locate `ControllerHasRouteAttributeTest.php`
- [x] `src/Tooling/Http/Api/PhpStan/FormRequestIsFinal.php` — co-locate `FormRequestIsFinalTest.php`

### Test Fixtures

- [x] Create valid Controller fixture (final, with `#[Route]`) in `tests/Fixtures/Tooling/Http/Api/PhpStan/`
- [x] Create invalid Controller fixtures (not final, missing Route attribute)
- [x] Create valid/invalid Request fixtures
- [x] Create `tests/Tooling/Concerns/GetsFixtures.php` trait for resolving fixture paths
- [x] Port `MakeControllerTest` from `make-commands`, adapting to the new Reference-based architecture

### Service Provider

- [x] Update `src/Support/Http/Api/Providers/Provider.php` to register `MakeController`, `MakeRequest`, `MakeTestForController` in `$commands`, keeping the existing `PagingInformation` mixin registration

### Tooling Configuration

- [x] Populate `tooling/phpstan/rules.neon` to register the 3 HTTP PHPStan rules
- [x] Register the `tooling.phpstan` key in `composer.json`'s `extra.tooling` section

### Verification

- [x] `composer test` (`testbench package:test --parallel`) — all existing + new tests pass
- [ ] `testbench tooling:phpstan` — confirms the 3 PHPStan rules load and execute
- [ ] Manual test: `php artisan make:controller` from a consuming app with `api` installed — interactive prompts work, correct files generated in expected directory structure

### Refactors

- [x] Consolidate `controller.stub` and `controller.single-resource.stub` into a single stub — conditionally replace model import/binding placeholders with empty strings for collection endpoints instead of maintaining two nearly-identical stubs

---

## 2. Items remaining in `make-commands` (evaluate disposition)

These rules have no counterpart in `entities` or `api` and need a home before `make-commands` can be archived:

### Builder Rules → suggest `aryeo/eloquent-filters`

The Rector rule's own TODO comment says "filterable should own this rule":

- [ ] `EloquentBuilderMustBeFinal` (PHPStan, `builder.final`) — enforces builder classes are `final`
- [ ] `EloquentBuilderMustHaveFilterableContract` (PHPStan, `builder.implements.filterable`) — enforces `Filterable` interface
- [ ] `EloquentBuilderMustUseHasFiltersTrait` (PHPStan, `builder.uses.hasFilters`) — enforces `HasFilters` trait
- [ ] `EloquentBuilderIsFilterable` (Rector) — auto-adds `HasFilters` trait and `Filterable` interface
- [ ] Associated test files: `EloquentBuilderMustBeFinalTest`, `EloquentBuilderMustHaveFilterableContractTest`, `EloquentBuilderMustUseHasFiltersTraitTest`, `EloquentBuilderIsFilterableTest`
- [ ] Associated fixtures: `Builder.php`, `BuilderNotFinal.php`, `BuilderWithoutContract.php`, `BuilderWithoutTrait.php`

### ServiceProvider Rule → suggest `aryeo/tooling-laravel` or `aryeo/entities`

- [ ] `ServiceProviderIsFinal` (PHPStan, `serviceProvider.final`) — generic rule, not specific to any domain
- [ ] Associated test file: `ServiceProviderIsFinalTest`
- [ ] Associated fixtures: `ServiceProvider.php`, `ServiceProviderNotFinal.php`

---

## 3. Items to remove from `make-commands`

### HTTP/API Code (moved to `api`)

- [ ] `src/Support/Console/Commands/MakeController.php`
- [ ] `src/Support/Console/Commands/MakeRequest.php`
- [ ] `src/Support/Console/Commands/MakeTestForController.php`
- [ ] `src/Support/Console/Commands/stubs/controller.stub`
- [ ] `src/Support/Console/Commands/stubs/controller.single-resource.stub`
- [ ] `src/Support/Console/Commands/stubs/request.stub`
- [ ] `src/Support/Console/Commands/stubs/controller-test.stub`
- [ ] `src/Support/Console/Concerns/WithDomainControllerContext.php`
- [ ] `src/Support/Console/Enums/Endpoints.php`
- [ ] `src/Support/Console/Enums/EndpointType.php`
- [ ] `src/Support/Console/Enums/ActionMethods.php`
- [ ] `src/Tooling/PHPStan/Rules/ControllerIsFinal.php`
- [ ] `src/Tooling/PHPStan/Rules/ControllerHasRouteAttribute.php`
- [ ] `src/Tooling/PHPStan/Rules/FormRequestIsFinal.php`
- [ ] `src/Support/Providers/MakeCommandsServiceProvider.php` (once all commands are rehomed)

### Duplicate Model Rules (already exist in `entities` with improved implementations)

The `entities` versions are scoped to `Entity`-implementing models, use the modern `shouldHandle`/`handle` pattern with `#[NodeType]`, and are `final`:

- [ ] `src/Tooling/PHPStan/Rules/ModelMustHaveCollection.php` — duplicate of `entities` `ModelMustHaveCollectedBy`
- [ ] `src/Tooling/PHPStan/Rules/ModelMustHaveEloquentBuilder.php` — duplicate of `entities` `ModelMustHaveUseEloquentBuilder`
- [ ] `src/Tooling/PHPStan/Rules/ModelMustHaveFactory.php` — duplicate of `entities` `ModelMustHaveUseFactory`

### Associated HTTP Test Files

- [ ] `tests/Support/Console/Commands/MakeControllerTest.php`
- [ ] `tests/Tooling/PHPStan/ControllerHasRouteAttributeTest.php`
- [ ] `tests/Tooling/PHPStan/ControllerIsFinalTest.php`
- [ ] `tests/Tooling/PHPStan/RequestIsFinalTest.php`

### Associated Duplicate Model Test Files

- [ ] `tests/Tooling/PHPStan/ModelMustHaveCollectionTest.php`
- [ ] `tests/Tooling/PHPStan/ModelMustHaveEloquentBuilderTest.php`
- [ ] `tests/Tooling/PHPStan/ModelMustHaveFactoryTest.php`

### HTTP Test Fixtures

- [ ] `tests/Fixtures/Controller.php`
- [ ] `tests/Fixtures/ControllerNotFinal.php`
- [ ] `tests/Fixtures/ControllerWithoutRouteAttribute.php`
- [ ] `tests/Fixtures/Request.php`
- [ ] `tests/Fixtures/RequestNotFinal.php`

### Duplicate Model Test Fixtures

- [ ] `tests/Fixtures/Post.php`
- [ ] `tests/Fixtures/Posts.php`
- [ ] `tests/Fixtures/PostWithoutBuilder.php`
- [ ] `tests/Fixtures/PostWithoutCollection.php`
- [ ] `tests/Fixtures/PostWithoutFactory.php`
- [ ] `tests/Fixtures/Factory.php`

### Tooling Configuration (update before archive)

- [ ] Remove HTTP rule registrations from `tooling/PHPStan/rules.neon`
- [ ] Remove duplicate model rule registrations from `tooling/PHPStan/rules.neon`
- [ ] Remove `aryeo/attribute-routing` dependency from `composer.json` (moved to `api`)
- [ ] Remove `SearchesDomainModels` trait reference (if local)

### Final

- [ ] Archive the `make-commands` repository once Section 2 items are rehomed
