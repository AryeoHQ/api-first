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
        'codes' => [],
    ],
    'conditional_get' => [
        'middleware_groups' => ['api'],
    ],
];
```

Publish the file with `php artisan vendor:publish --tag=api-first:config`.

The default group is `api`, so an upgrade to this release changes the error body of every route in that group whose controller lacks `#[LegacyErrors]`. An application whose `api` group serves an API with another error shape publishes the file first. It then names only the groups the renderer serves. Conditional GET reads its own key, `api-first.conditional_get.middleware_groups`, so a group removed here still answers `304`.

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
| `PreconditionFailed`                                  | 412     | `precondition.failed`                         |
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

A precondition makes a write endpoint honour `If-Match`. The client sends the `ETag` of a `GET` response, and the write runs only if the revisions that the endpoint names still match. [api-resource-schema](https://github.com/AryeoHQ/api-resource-schema) sets that `ETag`, and [eloquent-concurrency](https://github.com/AryeoHQ/eloquent-concurrency) maintains the revisions.

Extend `Precondition` and return a target. Then resolve the precondition between the authorizer and the validator, and wrap the write in `guard()`:

```php
use Support\Http\Api\Preconditions\Contracts\Target;
use Support\Http\Api\Preconditions\Precondition as BasePrecondition;
use Support\Http\Api\Preconditions\Targets\EntityTarget;

final class Precondition extends BasePrecondition
{
    public function target(): Target
    {
        /** @var WebhookEndpoint $webhookEndpoint */
        $webhookEndpoint = $this->route('webhook_endpoint');

        return EntityTarget::for($webhookEndpoint, ApiVersion::V2)->comparingSelf();
    }
}

public function __invoke(Authorizer $authorizer, Precondition $precondition, Validator $validator, WebhookEndpoint $webhookEndpoint): WebhookEndpointSchema
{
    $precondition->guard(fn () => $webhookEndpoint->update($validator->validated()));

    return $webhookEndpoint->toSchema(ApiVersion::V2);
}
```

`guard($write)` opens one transaction on the target's connection and locks the target's rows. It then compares `If-Match` against the revisions it read under that lock. It runs `$write` only on a match, and returns the closure's result. A concurrent write that lands between the request's start and the lock therefore still fails.

### Optional and required

A precondition is optional by default. A request without `If-Match` then runs the write. Set `$required` to answer such a request with `428`:

```php
protected bool $required = true;
```

| `If-Match`                                                           | Optional | Required |
| -------------------------------------------------------------------- | -------- | -------- |
| Absent                                                               | Runs     | 428      |
| `*`                                                                  | Runs     | Runs     |
| A list in which one tag matches                                      | Runs     | Runs     |
| A weak tag, an empty value, a malformed list, or no tag that matches | 412      | 412      |

A tag matches when its API version equals the target's, and each compared revision equals the stored one. The comparison ignores the digest and every revision the target does not compare. A `412` carries no `ETag`.

The precondition joins every `If-Match` line into one list. `*` matches only when it stands alone, so `*` beside a tag or on two lines is malformed. A malformed list matches nothing, even when it holds the current tag.

### What a target compares

`EntityTarget::for($model, $api)` takes a `Revisioned` model and starts with nothing to compare. Each method adds to the set:

| Method                          | Compares                                                    |
| ------------------------------- | ----------------------------------------------------------- |
| `comparingSelf()`               | The model's own revision                                    |
| `comparingNamed(...$revisions)` | Each named revision, such as the order of the model's items |
| `comparingAll()`                | The own revision and every declared named revision          |

A target that compares nothing throws `LogicException` when the precondition evaluates it. A named revision that the model does not declare throws `UndeclaredConcurrencyRevision`, and so does an application revision whose value is `self`. A compared revision whose column the model did not load throws `LogicException`, rather than comparing `0`.

Compare the revisions that the write depends on. A rename compares `comparingSelf()`. A move compares the order it changes, so a client can send the nested index tag `"v2.place_assets_order=4"`:

```php
/** @var PlaceAssetSubsection $placeAssetSubsection */
$placeAssetSubsection = $this->route('place_asset_subsection');

EntityTarget::for($placeAssetSubsection, ApiVersion::V2)
    ->comparingNamed(PlaceAssetSubsectionRevision::PlaceAssetsOrder);
```

`alsoLocking(...$models)` locks more rows in the same transaction, such as the source parent of a move between parents:

```php
EntityTarget::for($destination, ApiVersion::V2)
    ->comparingNamed(PlaceAssetSubsectionRevision::PlaceAssetsOrder)
    ->alsoLocking($source);
```

The target locks every row in morph class order, then in key order. [eloquent-ordering](https://github.com/AryeoHQ/eloquent-ordering) locks the parents of a move in the same order. A request and an ordering call that lock the same rows therefore take them in one order, and they do not wait on each other in a cycle. Each model needs a morph class, from a morph map or from `Relation::requireMorphMap(false)`.

The lock re-reads each row, so the write sees the committed attributes. A row that another request deleted gives a `404`. Every model must use the target's connection, because the transaction covers that connection only. `alsoLocking()` throws `LogicException` for a model on another connection.

### Answering a move or reorder

A `204` from a move or reorder carries the nested index tag of the order after the write. The route-bound parent holds the revision from before the write, because the lock reads it first. A tag built from that parent is stale, and the client's next request with it gets `412`. Build the tag from a parent that reflects the write:

```php
$change = $precondition->guard(fn () => $asset->move(Placement::last()->in($destination), expected: null));

return response()->noContent()->header(
    'ETag',
    (string) EntityTag::forNamedRevision($change->relation->parent, PlaceAssetSubsectionRevision::PlaceAssetsOrder, ApiVersion::V2),
);
```

The example uses eloquent-ordering. `$change` is the `OrderChange` that `move()` or `reorder()` returns, and `$change->relation->parent` is the parent that package refreshed after the write. api-first does not depend on that package. Any parent instance that reflects the write works.

### Check order

A request fails on the first check that does not pass:

1. `404`, from route model binding.
2. `403`, from the authorizer.
3. `428` or `412`, from the precondition.
4. `422`, from the validator.

The precondition compares the bound model when the container resolves it, which gives this order. The comparison inside `guard()` is the one that protects the write. `WriteEndpointMustOfferPrecondition` reports an `__invoke()` that takes a `Precondition` and never calls `guard()` on it.

## Conditional GET

A client that holds the `ETag` of a resource can send it back in `If-None-Match`. The package then answers `304 Not Modified` with the same `ETag` and no body, so the client keeps the copy it has.

```http
GET /api/webhook-endpoints/9d8f HTTP/1.1
If-None-Match: "v2.17.9f2c1a0b7e3d5c4a9f2c1a0b7e3d5c4a"

HTTP/1.1 304 Not Modified
ETag: "v2.17.9f2c1a0b7e3d5c4a9f2c1a0b7e3d5c4a"
```

The `ConditionalGet` middleware needs no setup. The provider adds it to each middleware group in `api-first.conditional_get.middleware_groups`, which defaults to `['api']`. That key is separate from `api-first.errors.middleware_groups`, so a group that keeps the framework's error bodies still answers `304`. It acts on a response only when all of these hold:

- The request is a `GET` or `HEAD`.
- The response is `200` and carries a representation `ETag`, the tag of a single resource. That tag ends in a digest of the body.
- The request sends a well-formed `If-None-Match`.

`If-None-Match` matches when it is `*` alone, or when an element of its comma-separated list equals the response `ETag`. The middleware joins every `If-None-Match` line into one list, and it ignores empty elements. Each element must be `"…"` or `W/"…"`. `*` beside a tag or on two lines is malformed, and a malformed list gets no `304`. The comparison is weak, so it ignores a `W/` prefix on either side. It compares the whole tag, digest included, because a changed body must answer `200`. A `304` drops `Content-Length` and `Content-Type`, and it keeps the `ETag`.

A nested index carries a tag such as `"v2.place_assets_order=4"`, but it never gets a `304`. That tag changes when the order changes, and it stays the same when an item changes.

Every other request passes through unchanged: a write, a response without an `ETag` such as a plain list, a nested index, and a response that is not `200`.

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

For update, delete, and instance-action endpoints, the command also generates a co-located `Precondition` through `make:precondition`. The controller takes it between the authorizer and the validator, and wraps the write in `guard()`:

```php
public function __invoke(Authorizer $authorizer, Precondition $precondition, Validator $validator, Order $order)
{
    $precondition->guard(function (): void {
        //
    });
}
```

Index, show, store, search, and resource-action endpoints get no precondition.

A precondition needs an entity that implements `Support\Database\Eloquent\Concurrency\Contracts\Revisioned`. For any other entity, an update, delete, or instance-action endpoint fails. The command then writes no file, and it exits non-zero with a message that names `Revisioned`.

### make:precondition

Generates a precondition that compares the own revision of the route-bound entity:

```bash
php artisan make:precondition Precondition --namespace='App\Http\Api\V1\Orders\Update' --entity='App\Entities\Orders\Order' --api-version=V1
```

```php
final class Precondition extends BasePrecondition
{
    public function target(): Target
    {
        /** @var Order $order */
        $order = $this->route('order');

        return EntityTarget::for($order, ApiVersion::V1)->comparingSelf();
    }
}
```

The command prompts for any option you omit. Change the target when the write depends on a named revision. See [What a target compares](#what-a-target-compares).

The entity must implement `Revisioned`. For any other entity, the command writes no file, and it exits non-zero with a message that names `Revisioned`.

### make:resource

Generates an API resource schema for an entity:

```bash
php artisan make:resource
```

The command prompts for an API version and entity, then generates a schema and its collection in `App\Http\Api\<Version>\<Plural>\Schemas` (e.g., `App\Http\Api\V1\Orders\Schemas\Order` and `App\Http\Api\V1\Orders\Schemas\Orders`). The generated schema automatically includes `$id` and `$resourceType` properties via the `InjectSchemaProperties` event listener.

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

| Rule                                               | Enforces                                                                                                                                                     |
| -------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `ControllerMustBeFinal`                            | Controllers must be declared `final`.                                                                                                                        |
| `ControllerMustHaveRoute`                          | Controllers must define their endpoint with a `#[Route]` attribute.                                                                                          |
| `ControllerMustHaveSingleCastableData`             | Controllers must not have more than one `CastableData` parameter.                                                                                            |
| `JsonResourceMustImplementSchema`                  | JSON resources must implement the `Schema` contract.                                                                                                         |
| `PreconditionMustSitBetweenAuthorizerAndValidator` | An `__invoke()` that takes a `Precondition` declares it after every authorizer and before the validator.                                                     |
| `RouteMustBeOnInvoke`                              | The `#[Route]` attribute must only appear on the `__invoke()` method.                                                                                        |
| `SchemaMustHaveId`                                 | Schemas must define a public `$id` property.                                                                                                                 |
| `SchemaMustHaveResourceType`                       | Schemas must define a public `$resourceType` property.                                                                                                       |
| `WriteEndpointMustOfferPrecondition`               | An `__invoke()` routed by `PUT`, `PATCH`, or `DELETE`, by `ANY` to a URI with a `{model}`, or by `POST` to `/{model}/actions/<action>`, takes a required `Precondition`: not nullable, with no default (`controller.precondition.missing`). A `POST` that creates is exempt. An `__invoke()` that takes a `Precondition` calls `guard()` on it (`controller.precondition.unguarded`). |
