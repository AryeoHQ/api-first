# API First

Provides the structure, conventions, and tooling for building APIs that conform to our [platform standards](https://github.com/AryeoHQ/vendor-platform-standards).

## Installation

```bash
composer require aryeo/api-first
```

The package auto-registers its service provider via Laravel package discovery.

## Endpoint Structure

API endpoints are organized by version and entity, following a consistent directory structure:

```
app/Http/Api/
└── V1/
    └── Jobs/
        ├── Index/
        │   ├── Controller.php
        │   └── Request.php
        ├── Show/
        │   ├── Controller.php
        │   └── Request.php
        ├── Store/
        │   ├── Controller.php
        │   └── Request.php
        └── Actions/
            └── Cancel/
                ├── Controller.php
                └── Request.php
```

Each controller is a `final` invokable class with a `#[Route]` attribute defining its name, URI, and HTTP method:

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
    public function __invoke(Request $request)
    {
        //
    }
}
```

Each controller is paired with a dedicated `FormRequest`:

```php
final class Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
```

### Generating Endpoints

The `make:controller` command scaffolds controllers and their paired requests:

```bash
php artisan make:controller
```

The command prompts for:

1. **API version** — select an existing version or create a new one (e.g., `V1`, `V2`)
2. **Entity** — the Eloquent model the endpoint serves
3. **Endpoint type** — REST or Action
4. **Endpoints** — for REST, select which standard actions to generate (index, show, store, update, delete, search); for Action, provide the action name

For each selected endpoint, the command generates both the controller and its request, placed in the correct directory per the conventions above.

### Standard Actions

| Action  | Method   | URI                                        |
| ------- | -------- | ------------------------------------------ |
| Index   | `GET`    | `/<resource>`                              |
| Search  | `POST`   | `/<resource>/search`                       |
| Show    | `GET`    | `/<resource>/{id}`                         |
| Store   | `POST`   | `/<resource>`                              |
| Update  | `PUT`    | `/<resource>/{id}`                         |
| Delete  | `DELETE` | `/<resource>/{id}`                         |

Non-standard actions follow the pattern:

| Scope    | Method | URI                                        |
| -------- | ------ | ------------------------------------------ |
| Instance | `POST` | `/<resource>/{id}/actions/<action>`        |

## Standardized Responses

### Response Envelope

All responses include a `meta` object alongside `data`. For paginated responses, `meta` contains `paging`, `filters`, and `sort`. For non-paginated responses (show, store, update, destroy), all three are `null`.

Paginated response:

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

Non-paginated response (no `filters` or `sort` in request):

```json
{
    "data": { "id": "...", "resource_type": "vendor.job" }
}
```

Non-paginated response (with `filters` and `sort` in request):

```json
{
    "data": { "id": "...", "resource_type": "vendor.job" },
    "meta": {
        "filters": {
            "status": "draft"
        },
        "sort": "-created_at"
    }
}
```

Pagination metadata is applied globally to all `JsonResource` collections via the `PagingInformation` mixin — no per-resource configuration is needed.

The package registers an `api-first` middleware group containing `AppendFilters` and `AppendSort`. These middleware inject `meta.filters` and `meta.sort` into any JSON response when the request contains `filters` or `sort` parameters, respectively. To activate them, add the group to your routes or append it to an existing middleware group:

```php
// In bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('api', [
        'api-first',
    ]);
})
```

Or apply it directly to routes:

```php
Route::middleware('api-first')->group(function () {
    // ...
});
```

**Paging** is `null` when the response has no cursors. When cursors are present, it includes `before`, `before_url`, `after`, `after_url`, and `size`.

**Filters** reflects the filters applied to the request. When the endpoint's form request implements `CastableData` (from `aryeo/request-casts`), filter values are returned with their cast types (e.g., `"1"` → `true` for a boolean cast). Otherwise, raw input values are returned. `null` when no filters are present.

**Sort** reflects the sort applied to the request, resolved from cast data when available. `null` when no sort is present.

### Scoped CastableData Binding

The package registers a scoped container binding for `CastableData`. On each request, it inspects the current route's controller parameters for a `CastableData` implementation, resolves the first match, and caches it for the request lifecycle. This is what allows the `filters` and `sort` resolvers to access cast values without coupling to a specific form request.

Controllers are limited to a single `CastableData` parameter — this is enforced by a PHPStan rule (see below).

### Request Context

The package adds `actor()` and `subject()` macros to `Illuminate\Http\Request`, providing semantic access to the authenticated user:

```php
$request->actor();   // The user performing the action
$request->subject(); // The user the action is being performed on behalf of
```

## PHPStan Rules

The package registers five PHPStan rules that enforce API conventions at static analysis time:

| Rule | Enforces |
| ---- | -------- |
| `ControllerIsFinal` | Controllers must be declared `final`. |
| `ControllerHasRouteAttribute` | Controllers must define their endpoint with a `#[Route]` attribute. |
| `RouteAttributeOnlyOnInvoke` | The `#[Route]` attribute must only appear on the `__invoke()` method. |
| `FormRequestIsFinal` | Form requests must be declared `final`. |
| `SingleCastableDataParameter` | Controllers must not have more than one `CastableData` parameter. |

These rules are automatically registered when using `aryeo/tooling-laravel` for static analysis.
