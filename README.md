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
        { "id": "...", "resource_type": "vendor.job" }
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
    "data": { "id": "...", "resource_type": "vendor.job" },
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

## Errors

The package renders API errors in the standard error envelope: exactly `errors` and `meta: {}`. Each error object holds a `code`, a `detail`, and an optional `source`:

```json
{
    "errors": [
        {
            "code": "validation.required",
            "detail": "The title field is required.",
            "source": { "pointer": "/title" }
        }
    ],
    "meta": {}
}
```

The renderer applies to a matched route that carries one of the configured middleware groups:

```php
// config/api-first.php
return [
    'errors' => [
        'middleware_groups' => ['api'],
    ],
];
```

Publish the file with `php artisan vendor:publish --tag=api-first:config`.

| Failure                                         | Status | Code                                          |
| ----------------------------------------------- | ------ | --------------------------------------------- |
| `AuthenticationException`, no bearer token      | 401    | `auth.missing_token`                          |
| `AuthenticationException`, with a bearer token  | 401    | `auth.invalid_token`                          |
| A failed authorizer or policy                   | 403    | `permission.denied`                           |
| A missing bound model                           | 404    | `not_found.resource`                          |
| A 405 raised inside a matched route             | 405    | `method.not_allowed`                          |
| `PreconditionFailed`                            | 412    | `precondition.failed`, with the `ETag` header |
| A failed validation rule, one object per rule   | 422    | `validation.<rule>`                           |
| `PreconditionRequired`                          | 428    | `precondition.required`                       |
| Throttling                                      | 429    | `rate_limit.exceeded`, with `Retry-After`     |
| Any other throwable                             | 500    | `server.internal_error`                       |

A validation code is the rule name in snake case. A size rule adds the attribute type: `validation.min_numeric`, `validation.max_string`. A rule object uses its class name: `PageSize` gives `validation.page_size`. A body field carries a `pointer` such as `/items/0/name`, and a query key carries a `parameter` such as `filters[status]`.

The framework keeps rendering these errors:

- An error raised before a route matches, such as an unknown path's 404 or the router's 405.
- A route outside the configured middleware groups.
- An HTTP exception whose status the table does not list, and an `HttpResponseException`.
- A validation failure with a message that no failed rule produced, such as one added by an `after` hook.

### Legacy errors

A controller marked `#[LegacyErrors]` keeps the framework's error bodies. `PreconditionFailed` and `PreconditionRequired` still render in the envelope, because those responses have no legacy shape:

```php
use Support\Http\Api\Errors\Attributes\LegacyErrors;

#[LegacyErrors]
final class Controller
{
    //
}
```

## Request Context

The package adds `actor()` and `subject()` macros to `Illuminate\Http\Request`, providing semantic access to the authenticated user:

```php
$request->actor();   // The user performing the action
$request->subject(); // The user the action is being performed on behalf of
```

## Generators

### make:controller

Scaffolds a controller with a co-located `Authorizer`, `Validator`, and test:

```bash
php artisan make:controller
```

The command prompts for:
1. **API version** — select an existing version or create a new one
2. **Entity** — the Eloquent model the endpoint serves
3. **Endpoint type** — REST or Action

For REST, it prompts for which endpoint to generate (index, show, store, update, delete, search). For Action, it prompts for the action name and scope (instance or resource).

Authorizer and Validator generation can be opted out with `--no-authorizer` and `--no-validator`.

### make:resource

Generates an API resource schema for an entity:

```bash
php artisan make:resource
```

The command prompts for an API version and entity, then generates a schema in the entity's namespace (e.g., `App\Http\Api\V1\Jobs`). The generated schema automatically includes `$id` and `$resourceType` properties via the `InjectSchemaProperties` event listener.

## Timestamps

All timestamps use RFC 3339 Extended format with millisecond precision:

```
2026-05-19T10:30:45.123+00:00
```

The package does three things automatically on boot:

1. **Swaps query grammars** — When a Postgres or SQLite connection starts, the package replaces Laravel's grammar with one that reads and writes `DateTime::RFC3339_EXTENDED`. Other database drivers keep their default grammar.
2. **Sets schema precision** — `Schema::defaultTimePrecision(3)` makes new timestamp columns store three decimal places.
3. **Configures Carbon serialization** — `Date::serializeUsing()` formats all Carbon instances (mutable and immutable) as RFC 3339 Extended when serialized to JSON.

No configuration is needed. The service provider handles everything through package discovery.

## Tooling

This package provides PHPStan rules that enforce API conventions at static analysis time. They are automatically registered when using `aryeo/tooling-laravel`.

| Rule                                    | Enforces                                                              |
| --------------------------------------- | --------------------------------------------------------------------- |
| `ControllerMustBeFinal`                 | Controllers must be declared `final`.                                 |
| `ControllerMustHaveRoute`               | Controllers must define their endpoint with a `#[Route]` attribute.   |
| `ControllerMustHaveSingleCastableData`  | Controllers must not have more than one `CastableData` parameter.     |
| `JsonResourceMustImplementSchema`       | JSON resources must implement the `Schema` contract.                  |
| `RouteMustBeOnInvoke`                   | The `#[Route]` attribute must only appear on the `__invoke()` method. |
| `SchemaMustHaveId`                      | Schemas must define a public `$id` property.                          |
| `SchemaMustHaveResourceType`            | Schemas must define a public `$resourceType` property.                |
