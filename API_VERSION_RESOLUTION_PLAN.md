# Plan: API Version Resolution Layering

Mirror the entity pattern for API version resolution. One trait to use, one method to call.

## Architecture

```
GeneratesForApiVersion (contract)
├── enforces: $apiVersion, resolveApiVersion(), apiVersionFromPrompt()

ResolvesApiVersion (the one trait consumers use)
├── use RetrievesApiVersionFromOption
├── use RetrievesApiVersionFromArgument
├── provides: $apiVersion, resolveApiVersion(), apiVersionFromPrompt(), scanning helpers
└── resolveApiVersion() = apiVersionFromOption() ?? apiVersionFromArgument() ?? apiVersionFromPrompt()

RetrievesApiVersionFromOption (leaf trait)
├── provides: apiVersionFromOption() — checks hasOption('api-version'), returns null if not defined
└── provides: getApiVersionInputOptions() — returns [InputOption('api-version', ...)]

RetrievesApiVersionFromArgument (leaf trait)
├── provides: apiVersionFromArgument() — checks hasArgument('api-version'), returns null if not defined
└── provides: getApiVersionInputArguments() — returns [InputArgument('api-version', ...)]
```

## Steps

### Phase 1: Create leaf traits *(parallel, no dependencies)*

- [x] Create `src/Support/Http/Api/Console/Concerns/RetrievesApiVersionFromOption.php`
   - `apiVersionFromOption(): ?Stringable` — guard with `hasOption('api-version')`, read, return null if empty
   - `getApiVersionInputOptions(): array` — returns `[new InputOption('api-version', null, VALUE_REQUIRED, 'The API version (e.g. V1).')]`

- [x] Create `src/Support/Http/Api/Console/Concerns/RetrievesApiVersionFromArgument.php`
   - `apiVersionFromArgument(): ?Stringable` — guard with `hasArgument('api-version')`, read, return null if empty
   - `getApiVersionInputArguments(): array` — returns `[new InputArgument('api-version', OPTIONAL, ...)]`

- [x] Create `src/Support/Http/Api/Console/Contracts/GeneratesForApiVersion.php`
   - Interface enforcing `public Stringable $apiVersion { get; }`, `resolveApiVersion(): void`, `apiVersionFromPrompt(): Stringable`

### Phase 2: Refactor ResolvesApiVersion *(depends on Phase 1)*

- [x] Refactor `src/Support/Http/Api/Console/Concerns/ResolvesApiVersion.php`
   - Add `use RetrievesApiVersionFromOption; use RetrievesApiVersionFromArgument;`
   - Keep `$apiVersion` property (`public protected(set) Stringable`)
   - Rename `versionFromPrompt()` → `apiVersionFromPrompt()` — keep scanning logic
   - Rename `getNextVersion()` → `getNextApiVersion()`
   - Rewrite `resolveApiVersion()` (renamed from `resolveVersion()`): chains `apiVersionFromOption() ?? apiVersionFromArgument() ?? apiVersionFromPrompt()`
   - Remove: `versionFromInput()`, `getVersionInputOptions()`
   - Keep: `NEW_API_VERSION_OPTION` constant, `getApiVersionOptions()` scanning helper

### Phase 3: Update consumers *(depends on Phase 2)*

- [x] Update `src/Support/Http/Api/Console/Commands/MakeController.php`
   - `$this->resolveVersion()` → `$this->resolveApiVersion()`
   - `...$this->getVersionInputOptions()` → `...$this->getApiVersionInputOptions()` in `getOptions()`
   - Trait usage (`use ResolvesApiVersion`) stays the same

- [x] Update `src/Support/Http/Api/Console/Concerns/RetrievesEndpointFromOptions.php`
   - Remove `--api-version` InputOption from `getEndpointOptions()`
   - Change `$endpoint` property hook: `apiVersion: $this->apiVersion` instead of `apiVersion: $this->option('api-version')`

- [x] Update `src/Support/Http/Api/Console/Commands/MakeRequest.php`
   - Add `use ResolvesApiVersion;`
   - Call `$this->resolveApiVersion();` in `handle()` before `parent::handle()`
   - Add `...$this->getApiVersionInputOptions()` in `getOptions()`

- [x] Update `src/Support/Http/Api/Console/Commands/MakeTestForController.php` — same as MakeRequest

### Phase 4: Verification *(depends on Phase 3)*

- [x] `./vendor/bin/testbench package:test --parallel` — all 46 tests pass

## Relevant files

### New
- `src/Support/Http/Api/Console/Contracts/GeneratesForApiVersion.php` — contract
- `src/Support/Http/Api/Console/Concerns/RetrievesApiVersionFromOption.php` — leaf trait
- `src/Support/Http/Api/Console/Concerns/RetrievesApiVersionFromArgument.php` — leaf trait

### Refactored
- `src/Support/Http/Api/Console/Concerns/ResolvesApiVersion.php` — composes leaf traits, owns `resolveApiVersion()` and `apiVersionFromPrompt()`

### Updated
- `src/Support/Http/Api/Console/Concerns/RetrievesEndpointFromOptions.php` — remove `--api-version`, read `$this->apiVersion`
- `src/Support/Http/Api/Console/Commands/MakeController.php` — rename method calls
- `src/Support/Http/Api/Console/Commands/MakeRequest.php` — add trait, call resolve, add option
- `src/Support/Http/Api/Console/Commands/MakeTestForController.php` — same as MakeRequest

## Reference implementation
- `entities/src/Support/Entities/Console/Contracts/GeneratesForEntity.php`
- `entities/src/Support/Entities/Console/Concerns/ResolvesEntity.php`
- `entities/src/Support/Entities/Console/Concerns/RetrievesEntityFromOption.php`
- `entities/src/Support/Entities/Console/Concerns/RetrievesEntityFromArgument.php`

## Decisions
- **No diamond:** `ResolvesApiVersion` composes two leaf traits that share nothing
- **Flat leaves:** Leaf traits don't `use` anything
- **Graceful guards:** `hasOption()`/`hasArgument()` checks mean unused input strategies are no-ops
- **Sub-commands skip scanning:** `apiVersionFromOption()` always resolves for them since `--api-version` is required; prompt path is never hit
- **Ordering constraint:** `RetrievesEndpointFromOptions` reads `$this->apiVersion` — consumer must call `resolveApiVersion()` before accessing `$endpoint`
