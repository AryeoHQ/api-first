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
            ├── Precondition.php
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
        'codes' => [],
    ],
];
```

Publish the file with `php artisan vendor:publish --tag=api-first:config`.

The default group is `api`, so an upgrade to this release changes the error body of every route in that group. An application whose `api` group serves an API with another error shape publishes the file first. It then names only the groups the renderer serves.

| Failure                                               | Status  | Code                                          |
| ----------------------------------------------------- | ------- | --------------------------------------------- |
| A 400 HTTP exception                                  | 400     | `request.malformed`                           |
| A non-empty JSON body that does not decode            | 400     | `request.malformed`                           |
| `AuthenticationException`, no bearer token            | 401     | `auth.missing_token`                          |
| `AuthenticationException`, with a bearer token        | 401     | `auth.invalid_token`                          |
| A failed authorizer or policy, `denyAsNotFound()` too | 403     | `permission.denied`                           |
| Passport's `MissingScopeException`                    | 403     | `permission.missing_scope`                    |
| A missing bound model                                 | 404     | `not_found.resource`                          |
| A 405 raised inside a matched route                   | 405     | `method.not_allowed`                          |
| `PreconditionFailed`                                  | 412     | `precondition.failed`, with the `ETag` header |
| A 415 HTTP exception                                  | 415     | `media_type.unsupported`                      |
| A failed validation rule, one object per rule         | 422     | `validation.<rule>`                           |
| `PreconditionRequired`                                | 428     | `precondition.required`                       |
| Throttling                                            | 429     | `rate_limit.exceeded`, with `Retry-After`     |
| A 503 HTTP exception                                  | 503     | `server.unavailable`, with any `Retry-After`  |
| Any other HTTP exception                              | Its own | `<category>.unspecified`                      |
| Any other throwable                                   | 500     | `server.internal_error`                       |

A validation code is the rule name in snake case. A size rule adds the attribute type: `validation.min_numeric`, `validation.max_string`. A rule object uses its class name: `PageSize` gives `validation.page_size`. Renaming a rule class therefore renames its public code. A body field carries a `pointer` such as `/items/0/name`, and a query key carries a `parameter` such as `filters[status]`.

The source has these limits:

- A key merged in `prepareForValidation()` gets a source, such as a `pointer`, that the client never sent.
- An array query key spells its index: `ids[1]`, not the `ids[]` the request sent.
- Validation never emits a `header` source yet, because nothing marks a validated attribute as a header.
- A query key that a POST request omits gets a `pointer`, not a `parameter`.

The framework keeps rendering these errors:

- An error raised before a route matches, such as an unknown path's 404 or the router's 405.
- A route outside the configured middleware groups.
- An `HttpResponseException`, because the application built its response.

### Uncatalogued errors

Every code the renderer returns must appear in the catalog. `resources/error-codes.php` holds the codes of the api-standards catalog, `docs/reference/error-codes.yaml`. Any other code becomes a placeholder, `<category>.unspecified`, with the detail "The request failed for a reason this API does not catalogue yet." The category comes from the status: `conflict.unspecified` for 409, `validation.unspecified` for 422. A status that the category table omits gives `request.unspecified` for 4xx and `server.unspecified` for 5xx, and the response keeps the status.

The placeholder covers these cases:

- An HTTP exception whose status the table does not list, such as 409.
- A `ValidationException` whose status is not 422. The response holds one error object.
- A validation message that no failed rule produced, such as one from an `after` hook or `ValidationException::withMessages()`.
- A rule whose derived code the catalog lacks, such as `Password` (`validation.password`), a closure, or `unique`.

Each validation message still gets its own error object and `source`.

Each placeholder logs a warning with the message `api-first.errors.uncatalogued`. The context holds `status`, `placeholder`, `derived_code`, `exception`, `rule`, `route`, and `method`. A log monitor on that message finds the codes to catalogue.

To catalogue a code, add it to the api-standards catalog, then list it in the application's config:

```php
// config/api-first.php
return [
    'errors' => [
        'middleware_groups' => ['api'],
        'codes' => ['validation.booking_window'],
    ],
];
```

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

## Preconditions

A `Precondition` evaluates `If-Match` on an update, a delete, or an instance action. It is a form request that sits between the `Authorizer` and the `Validator`, so the checks run in the order of the OCC evaluation table:

| Step | Check         | Response                                 |
| ---- | ------------- | ---------------------------------------- |
| 3    | Lookup        | 404 for a missing bound model            |
| 4    | Authorization | 403 from the `Authorizer`                |
| 5    | Preconditions | 428 or 412 from the `Precondition`       |
| 6    | Validation    | 422 from the `Validator`                 |

A precondition names its `Target`. `EntityTarget` tags a route-bound `Schemable` model with the ETag of its schema at one version:

```php
use Support\Http\Api\Preconditions\Contracts\Target;
use Support\Http\Api\Preconditions\Precondition as BasePrecondition;
use Support\Http\Api\Preconditions\Targets\EntityTarget;

final class Precondition extends BasePrecondition
{
    public function target(): Target
    {
        /** @var Job $job */
        $job = $this->route('job');

        return new EntityTarget($job, ApiVersion::V1);
    }
}
```

The controller runs its write through `guard()`:

```php
public function __invoke(Authorizer $authorizer, Precondition $precondition, Validator $validator, Job $job)
{
    $precondition->guard(fn () => $job->update($validator->validated()));

    return $job->toSchema(ApiVersion::V1);
}
```

The precondition evaluates `If-Match` when the request resolves:

- A request without `If-Match` passes, unless the precondition sets `$required = true`. A required precondition returns 428.
- A tag that matches the current ETag by strong comparison passes. `*` matches any current representation.
- The precondition reads every `If-Match` line as one list, and any listed tag can match. Lines that are each `*` read as one `*`.
- A weak tag, an empty value, or a stale tag returns 412 with the current `ETag`.

`guard()` makes the check atomic. It opens a transaction on the target's connection, calls `lock()` on the target, and evaluates `If-Match` again against the locked state. When another request commits a write after the first check, `guard()` returns 412, and the closure never runs. Otherwise the closure runs in the same transaction, and `guard()` returns its result.

`guard()` opens the transaction and takes the lock on every call, even on an optional endpoint whose request carries no `If-Match`. A row that another request deletes between route binding and `guard()` returns 404, and the closure never runs.

`EntityTarget::lock()` selects the row `FOR UPDATE` through the model's global scopes, so a row that another request soft-deletes also returns 404. A route that binds trashed models with `withTrashed()` locks a trashed entity as well. The lock refreshes the bound entity's attributes and leaves its loaded relations unchanged, so an eager load that the authorizer constrained stays in place.

After `lock()`, `tag()` hashes the locked row, not the bound entity. That instance holds no loaded relations, so every relation the schema or a `Contributor` reads loads inside the lock. A concurrent change to a relation alone therefore returns 412. The tag must not depend on a relation that the caller constrains, because the recheck loads that relation without the constraint.

`tag()` hashes the schema alone. A response ETag also covers the schema's `with()` and `additional()` data. An endpoint whose response adds such data therefore cannot use `EntityTarget`, because every write that sends its ETag returns 412.

An item of an ordered collection needs another lock order. `EntityTarget` locks the item row alone, and eloquent-ordering locks the parent row first, so a delete that races a reorder deadlocks. The eloquent-ordering README states the `Target` such an item needs.

### Collection targets

A collection action that changes order must require `If-Match` and compare it with the order tag of its collection. The application supplies a `Target` whose `tag()` returns that tag and whose `connection()` returns the parent's connection, and sets `$required = true`:

```php
use Illuminate\Database\ConnectionInterface;
use Support\Http\Api\Preconditions\Contracts\Target;
use Support\Http\Resources\Schemas\Tags\Tag;
use Support\Ordering\OrderedCollection;

final readonly class PhotoOrder implements Target
{
    public function __construct(private OrderedCollection $photos) {}

    public function tag(): Tag
    {
        return new Tag($this->photos->tag());
    }

    public function connection(): ConnectionInterface
    {
        return $this->photos->parent->getConnection();
    }

    public function lock(): void
    {
        $this->photos->parent->newQuery()->whereKey($this->photos->parent->getKey())->lockForUpdate()->firstOrFail();
    }
}

final class Precondition extends BasePrecondition
{
    protected bool $required = true;

    public function target(): Target
    {
        /** @var Place $place */
        $place = $this->route('place');

        return new PhotoOrder($place->orderedCollection('photos'));
    }
}
```

`PhotoOrder` covers a reorder inside one parent. A move between two parents must lock both parent rows in key order, or two opposite moves deadlock. The eloquent-ordering README carries that recipe.

## Request Context

The package adds `actor()` and `subject()` macros to `Illuminate\Http\Request`, providing semantic access to the authenticated user:

```php
$request->actor();   // The user performing the action
$request->subject(); // The user the action is being performed on behalf of
```

## Generators

### make:controller

Scaffolds a controller with a co-located `Authorizer`, `Precondition`, `Validator`, and test:

```bash
php artisan make:controller
```

The command prompts for:
1. **API version** — select an existing version or create a new one
2. **Entity** — the Eloquent model the endpoint serves
3. **Endpoint type** — REST or Action

For REST, it prompts for which endpoint to generate (index, show, store, update, delete, search). For Action, it prompts for the action name and scope (instance or resource).

Authorizer and Validator generation can be opted out with `--no-authorizer` and `--no-validator`.

The command generates a `Precondition` for an instance-scope update, delete, or action endpoint, and places its parameter between the `Authorizer` and the `Validator`. `--no-precondition` skips it, and `--precondition` adds it to any other instance endpoint, such as show. The generated class targets the route-bound entity with an `EntityTarget`, so `--precondition` on a resource-scope endpoint, such as index or store, fails. Such an endpoint supplies its own `Target`, as [Collection targets](#collection-targets) shows.

### make:precondition

Generates a precondition for an entity in a namespace:

```bash
php artisan make:precondition Precondition --namespace="App\Http\Api\V1\Jobs\Update" --entity="App\Entities\Jobs\Job" --api-version=V1
```

The command prompts for each option that the call omits.

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
| `PreconditionMustSitBetweenAuthorizerAndValidator` | An `__invoke()` must declare a `Precondition` after every `Authorizer` and before every `Validator`. |
