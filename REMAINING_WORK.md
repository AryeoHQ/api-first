# Remaining Work

## Task 1: Actor & Subject Request Macros

Expose `actor()` and `subject()` on `Request` via macros. Both return `$request->user()`.

- [x] Create `src/Support/Http/Api/Request/TokenContext.php` — mixin with `actor()` and `subject()` closures
- [x] Register in `Provider::register()` via `Request::mixin(new TokenContext)`
- [x] Add co-located `TokenContextTest.php`

---

## Task 2: PHPStan Rule — `#[Route]` Only on `__invoke()`

Enforce that `#[Route]` is only placed on `__invoke()`, never on other methods.

- [x] Create `src/Tooling/Http/Api/PhpStan/RouteAttributeOnlyOnInvoke.php`
- [x] Create co-located `RouteAttributeOnlyOnInvokeTest.php`
- [x] Create fixture `tests/Fixtures/Tooling/Http/Api/PhpStan/ControllerWithRouteOnOtherMethod.php`
- [x] Register in `tooling/phpstan/rules.neon`

---

## Task 3: Sort Metadata in Paginated Responses

Filter values are done. Sort remains.

- [ ] Design how `sort` values are resolved and included in paginated responses
- [ ] Desired response shape for sort: e.g., `{ "data": [...], "paging": {...}, "filter": {...}, "sort": {...} }`

---

## Task 4: Standardized Exception Responses

Ensure exception responses follow a consistent standard via `Handler::respondUsing()`.

- [ ] Decide error response JSON schema (RFC 7807, `{ "message", "status" }`, etc.)
- [ ] Decide whether validation `errors` keep their default shape
- [ ] Create `src/Support/Http/Api/Exceptions/StandardizesResponses.php`
- [ ] Register in Provider via `Handler::respondUsing()`
- [ ] Feature tests for 404, 403, 422, 500
- [ ] (Long-term) Framework PR to make `prepareException()` extensible
- [ ] For 4B: is extending `mapException()` / `$exceptionMap` to cover `prepareException()` cases the right framing for the PR?
