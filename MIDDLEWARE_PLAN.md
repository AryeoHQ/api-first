# Plan: Middleware-based filters & sort injection

## TL;DR
Replace the filters/sort logic in `PagingInformation` and the `HasResponseMeta` trait with two separate middleware classes — one for `meta.filters`, one for `meta.sort`. Each middleware checks for its respective key in the **request** (not response). Auto-registered into `api` middleware group. `PagingInformation` simplified to paging-only. `HasResponseMeta` deleted.

## Current Architecture
- `PagingInformation` mixin → handles `meta.paging`, `meta.filters`, `meta.sort` for paginated responses
- `HasResponseMeta` trait → handles null `meta` for single resources and non-paginated collections
- `Filters` class → resolves filter values from request (cast or raw)
- `Sort` class → resolves sort value from request (cast or raw)

## Target Architecture
- `PagingInformation` mixin → handles **only** `meta.paging` for paginated responses
- `AppendFilters` middleware → if request has `filters`, injects `meta.filters` into response
- `AppendSort` middleware → if request has `sort`, injects `meta.sort` into response
- Both auto-registered into `api` middleware group via Provider
- `HasResponseMeta`, `ResponseMetaResourceCollection` → deleted

## Steps

### Phase 1: Create Middleware (*parallel*)
- [x] 1. Create `src/Support/Http/Api/Resources/Json/Middleware/AppendFilters.php`
   - Check if request has `filters` key
   - After `$next($request)`, if response is `JsonResponse`, resolve filters via `Filters` class and set `meta.filters` on response data
   - If request has no `filters`, pass through unchanged
- [x] 2. Create `src/Support/Http/Api/Resources/Json/Middleware/AppendFiltersTest.php`
   - Test: request with filters → `meta.filters` present in response
   - Test: request without filters → response unchanged
   - Test: cast filter values from castable form request
   - Test: raw filter values from plain form request
- [x] 3. Create `src/Support/Http/Api/Resources/Json/Middleware/AppendSort.php`
   - Check if request has `sort` key
   - After `$next($request)`, if response is `JsonResponse`, resolve sort via `Sort` class and set `meta.sort` on response data
   - If request has no `sort`, pass through unchanged
- [x] 4. Create `src/Support/Http/Api/Resources/Json/Middleware/AppendSortTest.php`
   - Test: request with sort → `meta.sort` present in response
   - Test: request without sort → response unchanged

### Phase 2: Simplify PagingInformation
- [x] 5. Strip `Filters` and `Sort` from `PagingInformation::paginationInformation()` — return only `['meta' => ['paging' => ...]]`
- [x] 6. Update `PagingInformationTest` — remove all filter/sort assertions; tests assert only `meta.paging`

### Phase 3: Delete HasResponseMeta
- [x] 7. Delete `HasResponseMeta.php` (includes `ResponseMetaResourceCollection`)
- [x] 8. Delete `HasResponseMetaTest.php`
- [x] 9. Remove `use HasResponseMeta` from `ExampleResource.php` fixture

### Phase 4: Wire Up
- [x] 10. Register both middleware into `api` middleware group in Provider
- [x] 11. Update `README.md` — replace trait docs with middleware docs

### Phase 5: Verify
- [x] 12. `./vendor/bin/testbench tooling:pint --test`
- [x] 13. `./vendor/bin/testbench tooling:phpstan`
- [x] 14. `./vendor/bin/testbench tooling:rector --dry-run`
- [x] 15. `./vendor/bin/phpunit`

## Relevant files
- `src/Support/Http/Api/Resources/Json/Middleware/AppendFilters.php` — NEW
- `src/Support/Http/Api/Resources/Json/Middleware/AppendFiltersTest.php` — NEW
- `src/Support/Http/Api/Resources/Json/Middleware/AppendSort.php` — NEW
- `src/Support/Http/Api/Resources/Json/Middleware/AppendSortTest.php` — NEW
- `src/Support/Http/Api/Resources/Json/PaginatedResourceResponse/PagingInformation/PagingInformation.php` — simplify to paging-only
- `src/Support/Http/Api/Resources/Json/PaginatedResourceResponse/PagingInformation/PagingInformationTest.php` — remove filter/sort assertions
- `src/Support/Http/Api/Resources/Json/HasResponseMeta.php` — DELETE
- `src/Support/Http/Api/Resources/Json/HasResponseMetaTest.php` — DELETE
- `tests/Fixtures/Support/Http/Api/Resources/Json/ExampleResource.php` — remove trait
- `src/Support/Http/Api/Providers/Provider.php` — auto-register middleware into api group
- `README.md` — update docs

## Decisions
- Two middleware, not one — each checks for its key in the request independently
- Check **request** for `filters`/`sort` keys, not response for `data` key
- Auto-register into `api` middleware group via Provider
- `Filters` and `Sort` helper classes unchanged — middleware delegates to them
- `PagingInformation` keeps `meta.paging` only — no conflict with middleware since middleware writes `meta.filters`/`meta.sort` directly on serialized response data
- Non-paginated responses without filters/sort in request → no `meta.filters`/`meta.sort` injected. `meta.paging` also absent (no paginator). Need to decide: should middleware still set null values even when key not in request?

## Open Question
- For non-paginated responses (show/store/update/delete), the request typically won't have `filters` or `sort`. With this approach, those responses won't get `meta` at all. Is that acceptable, or should the middleware always inject null values regardless of request keys? The original spec said non-paginated responses should have `meta.paging: null, meta.filters: null, meta.sort: null`.
