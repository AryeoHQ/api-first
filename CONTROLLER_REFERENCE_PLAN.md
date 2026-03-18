# Plan: Onboard Controller as GenericClass

## TL;DR

Replace the custom `Endpoint` reference (which reimplements path resolution, namespace management, etc.) with a `Controller` class extending `GenericClass`. A `static make(...)` factory computes `baseNamespace` from domain inputs and calls `Reference::__construct`. Path resolution, namespace management, and `$test` come for free from the parent hierarchy.

## Steps

### Phase 1: Create Controller

- [x] Create `src/Support/Http/Api/References/Controller.php` extending `GenericClass`
  - Domain properties (set by factory): `$apiVersion`, `$entity`, `$endpointType`, `$endpointName`, `$actionMethod`
  - `static make(apiVersion, entity, endpointType, endpointName, actionMethod)` factory:
    - Computes subdirectory: Action → `Actions\StudlyName`, REST → `ucfirst(endpointName)`
    - Computes full namespace from `entity.baseNamespace\Http\Api\{version}\{plural}\{subdirectory}`
    - Calls `new static(name: 'Controller', baseNamespace: $namespace)`, sets domain properties
  - Computed properties (ported from `AsEndpoint`): `$routeName`, `$uri`, `$httpMethod`, `$isSingleResource`, `$modelBinding`
  - Companion references: `$authorizer`, `$validator` — constructed as `new Authorizer(name: 'Authorizer', baseNamespace: $this->namespace)`
  - Inherited for free: `$name`, `$namespace`, `$fqcn`, `$directory`, `$filePath`, `$test`, `$subNamespace` (null)

### Phase 2: Tests

- [x] Create `src/Support/Http/Api/References/ControllerTest.php`
  - Test factory produces correct `$namespace`, `$fqcn`, `$directory`, `$filePath`
  - Test computed domain properties: `$routeName`, `$uri`, `$httpMethod`, `$isSingleResource`, `$modelBinding`
  - Test companions: `$authorizer->filePath`, `$validator->filePath`, `$test->filePath`
  - Test both REST and Action endpoint types

### Phase 3: Verification

- [x] Run verification — pint, phpstan, rector --dry-run, phpunit

## Relevant files

- `vendor/aryeo/tooling-laravel/.../GenericClass.php` — parent: `$test`, `fromFqcn()`
- `vendor/aryeo/tooling-laravel/.../Reference.php` — `final __construct(name, baseNamespace)`, `$namespace`, `$fqcn`
- `vendor/aryeo/tooling-laravel/.../ResolvesPaths.php` — `$directory`, `$filePath` inherited
- `src/Support/Http/Api/References/Concerns/AsEndpoint.php` — source for computed properties to port
- `vendor/aryeo/request-authorizers-validators/.../Authorizer.php` / `Validator.php` — companion references

## Decisions

- No contract interface — use concrete Controller directly
- No `$request` — dropped
- Old `Endpoint` + `AsEndpoint` + `EndpointContract` stay until MakeController wiring (next phase)
- `fromFqcn()` inherited but not meaningful for Controller (no domain props) — harmless to leave
