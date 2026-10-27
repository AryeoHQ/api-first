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
        "filters": {
            "status": "draft",
            "created_at": "2025-01-01.."
        },
        "paging": {
            "before": null,
            "before_url": null,
            "after": "eyJ...",
            "after_url": "https://api.example.com/v2beta1/jobs?filters%5Bstatus%5D=draft&filters%5Bcreated_at%5D=2025-01-01..&sort=-created_at&paging%5Bsize%5D=25&paging%5Bafter%5D=eyJ...",
            "size": 25
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

`meta.paging` is `null` when the response has no cursors. Otherwise it holds `before`, `before_url`, `after`, `after_url`, and `size`.

#### Standards paging

A `cursorPaginate()` call that passes no cursor pages by `paging[after]`, `paging[before]`, and `paging[size]`. The package registers `Support\Http\Api\Pagination\CursorResolver` as Laravel's cursor resolver, so this applies to an Eloquent query, a relation, and `DB::table()` alike. A caller that passes its own cursor bypasses the resolver. The caller passes the page size. The list's resource and collection use `WithDirectionalMeta`, which throws a `LogicException` when it must emit a cursor that the resolver did not see.

```php
$size = $validator->safe()->integer('paging.size') ?: 10;

return new Jobs(Job::query()->orderBy('created_at')->orderBy('id')->cursorPaginate($size));
```

A validator may also use `PagesByCursor`, so that its paging errors arrive with its other field errors:

```php
use Support\Http\Api\Request\Concerns\PagesByCursor;
use Support\Http\Validator as BaseValidator;

final class Validator extends BaseValidator
{
    use PagesByCursor;

    public function rules(): array
    {
        return [...$this->pagingRules(), 'sort' => ['sometimes', 'string']];
    }

    public function validationData(): array
    {
        return $this->query->all();
    }
}
```

- The resolver validates `paging[size]`, `paging[after]`, and `paging[before]` from the query string, and it reads the cursor from there. A JSON body never changes the page. The resolver needs no validator.
- `pagingRules()` validates whatever `validationData()` returns. A list request should return the query bag there, as above.
- `paging[size]` accepts 1 to 100, and returns 422 for any other value. The OCC error renderer renders it as `validation.page_size`. The resolver validates the size but does not apply it: a caller that passes `null` gets the model's `getPerPage()`, which is 15.
- The key sets the direction: `paging[after]` returns the items that follow the cursor, and `paging[before]` the items that precede it.
- Each cursor the list issues carries a signature made with the application key. The signature binds the cursor to the request's path and to its `sort`, `filters`, and search `query` query keys. The order of the `filters` keys does not matter, but the order of a filter's values does. A blank value binds as an absent one, because the list's own URLs omit it. `paging[...]`, `include`, and every other query key are not bound. A JSON body is never bound.
- A cursor that passes verification but meets a query with a different order throws Laravel's `UnexpectedValueException`, which renders 500. This happens when a deploy changes a list's order while clients hold its cursors. It also happens when one request makes a second `cursorPaginate()` call on a query with a different order.
- A value that fails verification throws a `ValidationException` on `paging.after` or `paging.before`, and the `Support\Http\Api\Rules\Cursor` rule reports the failure. That covers a value longer than 1024 bytes, and an unsigned or undecodable value. It also covers a changed payload or signature, and a cursor sent to another path or with another `sort`, `filters`, or `query`. The OCC error renderer renders it as `validation.cursor`. Without that renderer, Laravel's default 422 shape applies.
- A change to `APP_KEY` invalidates every outstanding cursor. A client that sends one gets 422 once, and it must start again from the first page.
- A request with both keys returns 422 on `paging.before`. The OCC error renderer renders it as `validation.prohibits_parameter`.
- Any other `paging[...]` key, such as `paging[offset]`, returns 422 on that key, for example `paging.offset`. A `paging` value that is not an array returns 422 on `paging`. The OCC error renderer renders both as `validation.unknown_parameter`.
- Outside an HTTP request, such as in a console command or a queued job, the request carries no `paging`. The resolver then returns no cursor, and `cursorPaginate()` returns the first page.
- `WithDirectionalMeta` writes `meta.paging` with the same keys. Each URL repeats the request's query keys and carries its own `paging[after]` or `paging[before]` cursor.

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

The package supports Postgres only. A cursor encodes an Eloquent date attribute as RFC 3339 Extended at millisecond precision, so order a cursor list only by a date column of at most millisecond precision: a `timestamp(6)` column can repeat or skip rows at a page boundary.

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
