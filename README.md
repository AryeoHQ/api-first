# API First

Provides the structure, conventions, and tooling for building APIs that conform to our [platform standards](https://github.com/AryeoHQ/vendor-platform-standards).

## Installation

```bash
composer require aryeo/api-first
```

> Requires PHP 8.4+

The package auto-registers its service provider via Laravel package discovery.

## Endpoint Structure

API endpoints are organized by version and entity:

```
app/Http/Api/
└── V1/
    └── Jobs/
        ├── Index/
        │   ├── Authorizer.php
        │   ├── Controller.php
        │   ├── ControllerTest.php
        │   └── Validator.php
        ├── Show/
        │   ├── Authorizer.php
        │   ├── Controller.php
        │   ├── ControllerTest.php
        │   └── Validator.php
        └── Cancel/
            ├── Authorizer.php
            ├── Controller.php
            ├── ControllerTest.php
            └── Validator.php
```

Each controller is a `final` invokable class with a `#[Route]` attribute:

```php
use Support\Routing\Attributes\Route;
use Support\Routing\Enums\Method;

final class Controller
{
    #[Route(
        name: 'api.v1.jobs.index',
        uri: 'api/v1/jobs',
        methods: Method::Get,
    )]
    public function __invoke(Authorizer $authorizer, Validator $validator)
    {
        //
    }
}
```

### Standard Actions

| Action | Method   | URI                     |
| ------ | -------- | ----------------------- |
| Index  | `GET`    | `/<resource>`           |
| Search | `POST`   | `/<resource>/search`    |
| Show   | `GET`    | `/<resource>/{id}`      |
| Store  | `POST`   | `/<resource>`           |
| Update | `PATCH`  | `/<resource>/{id}`      |
| Delete | `DELETE` | `/<resource>/{id}`      |

Non-standard actions are always `POST` and can be scoped to an instance or resource:

| Scope    | Method | URI                                 |
| -------- | ------ | ----------------------------------- |
| Instance | `POST` | `/<resource>/{id}/actions/<action>` |
| Resource | `POST` | `/<resource>/actions/<action>`      |

## Response Envelope

Paginated responses include a `meta` object alongside `data`:

```json
{
    "data": [
        { "id": "...", "resource_type": "vendor.job", "resource_version": "v1" }
    ],
    "meta": {
        "paging": {
            "before": "YJApTcN4PAgEXP9mRvaQ",
            "before_url": "https://example.com/api/v1/jobs?paging[cursor]=YJApTcN4PAgEXP9mRvaQ",
            "after": "F3g_cWwV8hu3zMLlHdAw",
            "after_url": "https://example.com/api/v1/jobs?paging[cursor]=F3g_cWwV8hu3zMLlHdAw",
            "size": 10
        },
        "filters": {
            "status": "draft",
            "created_at": "2025-01-01.."
        },
        "sort": "-created_at"
    }
}
```

Non-paginated responses omit `meta` unless the request includes `filters` or `sort`:

```json
{
    "data": { "id": "...", "resource_type": "vendor.job", "resource_version": "v1" },
    "meta": {
        "filters": { "status": "draft" },
        "sort": "-created_at"
    }
}
```

### Paging

Pagination metadata is applied globally to all `JsonResource` collections via a mixin — no per-resource configuration is needed. `paging` is present when the response has cursors and includes `before`, `before_url`, `after`, `after_url`, and `size`.

Cursor pagination uses `paging[cursor]` as the query parameter rather than `cursor`.

### Filters & Sort

The package registers an `api-first` middleware group containing `AppendFilters` and `AppendSort`. These inject `meta.filters` and `meta.sort` into any JSON response when the request contains the corresponding parameters.

To activate, append the group to your routes:

```php
// In bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('api', [
        'api-first',
    ]);
})
```

When the endpoint's form request implements `CastableData` (from `aryeo/request-casts`), filter and sort values are returned with their cast types (e.g., `"1"` → `true` for a boolean cast). Otherwise, raw input values are returned.

### Scoped CastableData Binding

The package registers a scoped container binding for `CastableData`. On each request, it inspects the current route's controller parameters for a `CastableData` implementation, resolves the first match, and caches it for the request lifecycle. This is what allows the filters and sort resolvers to access cast values without coupling to a specific form request.

Controllers are limited to a single `CastableData` parameter — this is enforced by a PHPStan rule (see below).

## Schema Versioning

The package builds on [`aryeo/api-resource-schema`](https://github.com/AryeoHQ/api-resource-schema) for schema versioning. Refer to that package's README for core setup: defining a `Version` enum, applying `#[Version]` to schemas, and `#[UseSchema]` to models.

`api-first` hooks into the `make:resource` command via an `InjectSchemaProperties` listener that automatically adds `$id`, `$resourceType`, and `$resourceVersion` properties to every generated schema class.

## Event Logging

The package integrates [`aryeo/event-log`](https://github.com/AryeoHQ/event-log) to provide structured event logging for all entities.

Every model must implement the `Loggable` contract and use the `LogsSchemas` trait:

```php
use Support\Entities\Contracts\Entity;
use Support\Entities\Models\Concerns\LogsSchemas;
use Support\Events\Log\Contracts\Loggable;
use Support\Http\Resources\Schemas\Contracts\Schemable;

class Job extends Model implements Entity, Schemable, Loggable
{
    use LogsSchemas;
}
```

`LogsSchemas` provides:
- `TransformsToSchema` — attribute-driven schema resolution from `api-resource-schema`
- `toLoggable()` — returns a collection of all schema versions for the model, used as the event log payload

Model events participate in the event log when they implement `Recordable` or `RecordableAfterCommit` (see `make:event` below).

## Timestamps

The package enforces RFC 3339 Extended timestamps with millisecond precision across the stack:

- **Database**: set `date_format` on your connection to store timestamps with millisecond precision:
  ```php
  // config/database.php
  'connections' => [
      'pgsql' => [
          'date_format' => \DateTime::RFC3339_EXTENDED, // 2026-05-19T10:30:45.123+00:00
      ],
  ],
  ```
- **Migrations**: `Schema::defaultTimePrecision(3)` is applied automatically
- **Carbon serialization**: defaults to RFC 3339 Extended for JSON and string output

When `date_format` is configured, the package swaps the query grammar to use the configured format for date operations.

> **Note:** Laravel's default timestamp precision is `0` (seconds). Any existing timestamp columns created before this package was installed will need a migration to update their precision to `3` in order to participate in these settings.

## Request Context

The package adds `actor()` and `subject()` macros to `Illuminate\Http\Request`, providing semantic access to the authenticated user:

```php
$request->actor();   // The user performing the action
$request->subject(); // The user the action is being performed on behalf of
```

## Generators

### make:model

Scaffolds a full entity model with all conventions pre-wired:

```bash
php artisan make:model Job
```

The generated model implements `Entity`, `Schemable`, and `Loggable`, and uses the `LogsSchemas` trait. When `--events` is passed, the command regenerates all model events with `Recordable` / `RecordableAfterCommit` contracts for event-log participation.

### make:controller

Scaffolds a controller with a co-located `Authorizer`, `Validator`, and test:

```bash
php artisan make:controller
```

The command prompts for:
1. **API version** — selected from schema versions available on the entity (resolved via `#[UseSchema]` attributes)
2. **Entity** — the Eloquent model the endpoint serves
3. **Endpoint type** — REST or Action

For REST, it prompts for which endpoint to generate (index, show, store, update, delete, search). For Action, it prompts for the action name and scope (instance or resource).

The generated controller includes a typed return (the entity's schema or schema collection for the selected version). Authorizer and Validator generation can be opted out with `--no-authorizer` and `--no-validator`.

### make:resource

Generates an API resource schema for an entity:

```bash
php artisan make:resource
```

The command prompts for a version (from the configured `Version` enum) and entity. The generated schema automatically includes `$id`, `$resourceType`, and `$resourceVersion` properties. After generation, the command reminds you to add `#[UseSchema]` and `#[UseSchemaCollection]` attributes to the model.

### make:event

Scaffolds entity events with optional event-log support:

```bash
php artisan make:event Creating --entity=App\\Entities\\Jobs\\Job --recordable
```

| Flag                        | Effect                                                    |
| --------------------------- | --------------------------------------------------------- |
| `--recordable`              | Implements `Recordable` (recorded immediately)            |
| `--recordable-after-commit` | Implements `RecordableAfterCommit` (recorded post-commit) |

Recordable events are decorated with `#[Alias]`, `#[IdentifiesLoggable]`, and the `HasLoggable` trait.

> **Convention:** `ing` events (e.g., `Creating`) use `Recordable` (recorded immediately) and `ed` events (e.g., `Created`) use `RecordableAfterCommit` (recorded after the transaction commits). This is applied automatically for events created by `make:model`.

### make:collection

Scaffolds a typed Eloquent collection that implements `SchemableCollection`:

```bash
php artisan make:collection --entity=App\\Entities\\Jobs\\Job
```

## Tooling

This package provides PHPStan rules that enforce API conventions at static analysis time. They are automatically registered when using `aryeo/tooling-laravel`.

| Rule                                    | Enforces                                                              |
| --------------------------------------- | --------------------------------------------------------------------- |
| `ControllerMustBeFinal`                 | Controllers must be declared `final`.                                 |
| `ControllerMustHaveRoute`               | Controllers must define their endpoint with a `#[Route]` attribute.   |
| `ControllerMustHaveSingleCastableData`  | Controllers must not have more than one `CastableData` parameter.     |
| `JsonResourceMustImplementSchema`       | JSON resources must implement the `Schema` contract.                  |
| `ModelMustImplementLoggable`            | Models must implement the `Loggable` contract.                        |
| `LoggableMustUseLogsSchemas`            | `Loggable` models must use the `LogsSchemas` trait.                   |
| `RouteMustBeOnInvoke`                   | The `#[Route]` attribute must only appear on the `__invoke()` method. |
| `SchemaMustHaveId`                      | Schemas must define a public `$id` property.                          |
| `SchemaMustHaveResourceType`            | Schemas must define a public `$resourceType` property.                |
| `SchemaMustHaveResourceVersion`         | Schemas must define a public `$resourceVersion` typed as the configured `Version` enum. |
