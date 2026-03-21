# Plan: Override `make:resource` with entity-aware wrapper

## TL;DR
Create a thin `MakeResource` command in api-first that prompts for entity + API version, computes the target namespace, and delegates to api-resource-schema's `MakeResource` via `$this->call(UpstreamClass::class, [...])`. Registered with the same `make:resource` name — wins via provider boot order (api-first depends on api-resource-schema, so boots last).

## Architecture

**Our command** (`Command`, NOT `GeneratorCommand`):
- Name: `make:resource` (overrides upstream)
- Prompts: entity (via `RetrievesEntity`), API version (via `ResolvesApiVersion`)
- Derives namespace: `App\Http\Api\{apiVersion}\Schemas`
- Derives name: entity name (e.g., `Post`), or `{name}Collection` with `--collection`
- Delegates: `$this->call(UpstreamMakeResource::class, ['name' => ..., '--namespace' => ...])`

**Override mechanism**: Last-write-wins. api-resource-schema registers in `boot()`. api-first also registers in `boot()`. Since api-first depends on api-resource-schema in `composer.json`, Laravel discovers/boots api-resource-schema first → api-first boots last → our `make:resource` overwrites theirs.

**`$this->call()` with class name**: `resolveCommand()` detects `class_exists()` → resolves via `$this->laravel->make($class)` → bypasses name-based lookup → no recursion. Same pattern already used by `MakeController` for `MakeAuthorizer`/`MakeValidator`.

## Steps

### Phase 1: Create command
- [ ] 1. Create `src/Support/Http/Api/Console/Commands/MakeResource/MakeResource.php`
   - Extends `Illuminate\Console\Command` (not `GeneratorCommand`)
   - Uses `RetrievesEntity`, `ResolvesApiVersion`, `SearchesClasses`
   - `$name = 'make:resource'`
   - `handle()`: resolveApiVersion → resolveEntity → compute namespace + name → `$this->call(UpstreamMakeResource::class, [...])`
   - Options: `--entity`, `--api-version`, `--collection`, `--force`

### Phase 2: Create test
- [ ] 2. Create `src/Support/Http/Api/Console/Commands/MakeResource/MakeResourceTest.php`
   - Test: generates a schema file at the correct entity-derived path
   - Test: generates a collection file with `--collection`
   - Test: passes `--force` through to upstream

### Phase 3: Register command + move to `boot()`
- [ ] 3. In `src/Support/Http/Api/Providers/Provider.php`: move ALL command registration (`MakeController` + `MakeResource`) from `register()` to `boot()` for correct override timing and consistency

### Phase 4: Verify
- [ ] 4. `./vendor/bin/testbench tooling:pint`
- [ ] 5. `./vendor/bin/testbench tooling:phpstan`
- [ ] 6. `./vendor/bin/testbench tooling:rector --dry-run`
- [ ] 7. `./vendor/bin/phpunit`

## Relevant files
- `src/Support/Http/Api/Console/Commands/MakeResource/MakeResource.php` — NEW
- `src/Support/Http/Api/Console/Commands/MakeResource/MakeResourceTest.php` — NEW
- `src/Support/Http/Api/Providers/Provider.php` — add MakeResource, move commands to `boot()`

## Key decisions
- Plain `Command`, not `GeneratorCommand` — no file generation, pure delegation
- Same `make:resource` name — full override when both packages installed
- Class-based `$this->call()` — avoids name-based recursion
- Provider registration in `boot()` — guarantees we overwrite api-resource-schema's registration
- Namespace: `App\Http\Api\{apiVersion}\Schemas`
- Schema class name = entity name (e.g., `Post` not `PostSchema`) — the `Schemas` namespace provides context
