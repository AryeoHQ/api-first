# Plan: Onboard event-log into api-first

## TL;DR

Bridge event-log with api-first by providing a `LogsSchemas` model trait (implements `Loggable::toLoggable()` via schema collection), injecting `$resourceVersion` into generated schemas, enforcing conventions via PHPStan rules, and overriding `make:model`/`make:event` to generate Recordable events automatically.

## How `LogsSchemas` Works

```php
public function toLoggable(): Collection
{
    return $this->schemas->map(fn (string $schema) => $schema::make($this));
}
```

Returns a `Collection<Schema&JsonResource>`. Event-log normalizes via `Collection::toArray()`. Each schema self-describes via `$id`, `$resourceType`, `$resourceVersion`. Stored JSON:

```json
[
  {"id": "uuid", "resource_type": "banana", "resource_version": "v1", ...},
  {"id": "uuid", "resource_type": "banana", "resource_version": "v2", ...}
]
```

## Steps

### Phase 1: Core `LogsSchemas` Trait

- [ ] 1. Add PSR-4 mapping `"Support\\Entities\\" => "src/Support/Entities"` in `composer.json`
- [ ] 2. Create `src/Support/Entities/Models/Concerns/LogsSchemas.php`
- [ ] 3. Co-located test `LogsSchemasTest.php`

### Phase 2: `$resourceVersion` Injection

- [ ] 4. Update `InjectSchemaProperties` — resolve version class from `config('api-resource-schema.version')`, push import via `$event->imports` + property `public {VersionClass} $resourceVersion { get => $this->schemaVersion; }`
- [ ] 5. Update existing test if one exists

### Phase 3: PHPStan Rules

- [ ] 6. Rule: Entity Model must implement `Loggable` (targets `Model` & `Entity`)
- [ ] 7. Rule: Entity Model must use `LogsSchemas` (targets `Model` & `Entity`)
- [ ] 8. Register in `tooling/phpstan/rules.neon`
- [ ] 9. Fixtures in `tests/Fixtures/Tooling/` (valid + invalid)
- [ ] 10. Co-located rule tests

### Phase 4: Generator Command Overrides

- [ ] 11. **`make:event` override** — extends upstream `MakeEvent`, overrides `buildClass()` + `getOptions()`. When `--recordable`/`--recordable-after-commit` passed, appends:
    - `Recordable`/`RecordableAfterCommit` interface (alongside `ForEntity`)
    - `HasLoggable` trait (alongside `HasEntity` — already includes `SerializesModels`)
    - `#[Alias('entity.event-name')]` class-level attribute (via `$this->reference->semanticName`)
    - `#[IdentifiesLoggable]` on model property (alongside `#[IdentifiesEntity]`)
- [ ] 12. **`make:model` override** — extends upstream `MakeModel`:
    - `handle()`: calls `parent::handle()`, then regenerates events with our `MakeEvent` + `--force` + suffix logic (`*ing` → `--recordable`, `*ed` → `--recordable-after-commit`)
    - `buildClass()`: calls parent, then injects `Loggable`, `LogsSchemas`, `Schemable`, `TransformsToSchema`
- [ ] 13. Register both in Provider's `bootCommands()` inside `$this->app->booted(...)`



## Generated Output Examples

### Event (`--recordable-after-commit`):

```php
#[Alias('banana.updated')]
final class Updated implements ForEntity, RecordableAfterCommit
{
    use Dispatchable;
    use HasEntity;
    use HasLoggable;

    #[IdentifiesEntity]
    #[IdentifiesLoggable]
    public readonly Banana $banana;

    public function __construct(Banana $banana)
    {
        $this->banana = $banana;
    }
}
```

### Event (`--recordable`):

```php
#[Alias('banana.updating')]
final class Updating implements ForEntity, Recordable
{
    use Dispatchable;
    use HasEntity;
    use HasLoggable;

    #[IdentifiesEntity]
    #[IdentifiesLoggable]
    public readonly Banana $banana;

    public function __construct(Banana $banana)
    {
        $this->banana = $banana;
    }
}
```

### Model:

```php
class Banana extends Model implements Entity, Schemable, Loggable
{
    use HasFactory;
    use HasUuids;
    use LogsSchemas;
    use TransformsToSchema;

    protected $dispatchesEvents = [
        'creating' => Events\Creating::class,
        'created' => Events\Created::class,
        // ...
    ];
}
```

## Files

### New (11 files):

- `src/Support/Entities/Models/Concerns/LogsSchemas.php` + `LogsSchemasTest.php`
- `src/Support/Http/Api/Console/Commands/MakeEvent/MakeEvent.php`
- `src/Support/Http/Api/Console/Commands/MakeModel/MakeModel.php`
- `src/Tooling/Http/Api/PhpStan/EntityMustImplementLoggable.php` + test
- `src/Tooling/Http/Api/PhpStan/EntityMustUseLogsSchemas.php` + test
- `tests/Fixtures/Tooling/` — 3 fixtures (valid entity, missing Loggable, missing LogsSchemas)

### Modified (6 files):

- `composer.json` — PSR-4 mapping
- `InjectSchemaProperties.php` — add `$resourceVersion`
- `Provider.php` — register commands
- `tooling/phpstan/rules.neon` — register rules

## Verification

- [ ] `./vendor/bin/phpunit` — all tests pass
- [ ] `./vendor/bin/testbench tooling:phpstan` — no errors
- [ ] `./vendor/bin/testbench tooling:pint` — clean
- [ ] `./vendor/bin/testbench tooling:rector --dry-run` — no violations
- [ ] Banana `toLoggable()` returns Collection with `resource_version` in each item
- [ ] `make:event --recordable-after-commit` generates correct output
- [ ] `make:model` generates model + events with correct Recordable contracts

## Decisions

- Namespace: `Support\Entities\Models\Concerns\LogsSchemas`
- Append (not replace) ForEntity/HasEntity/IdentifiesEntity
- `HasLoggable` already includes `SerializesModels`
- `#[Alias]` = class attribute, `#[IdentifiesLoggable]` = property attribute
- `$resourceVersion` typed as concrete enum from config
- `#[UseSchema]` added later by `make:resource`, not at model gen time
- Suffix logic: `*ing` → `Recordable`, `*ed` → `RecordableAfterCommit`
