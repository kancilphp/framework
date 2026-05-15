# Changelog

## [0.10.0] — 2026-05-15

### Performance
- Boot: cache check (`Gate::preRun()`) moved before DB alias, helpers, hooks — cache hit exits before expensive operations
- Boot: `Env::load()` moved after cache check — cache hit no longer runs full env loading (60+ function calls)
- Boot: removed duplicate `require env.php` for `APP_DEBUG` — uses `Config::get()` instead
- Cache file driver: replaced `json_encode`/`json_decode` with raw format `{expires}:{hash}\n{html}` — zero parse overhead per cache hit
- Cache Redis driver: removed `json_encode`/`json_decode` — stores/gets raw string directly
- Cache: pre-computed md5 hash stored in file header — `serveETagAndExit()` uses it directly, no `md5(17KB)` per cache hit
- Cache: added `$lastHash` static property — ETag hash available without recomputation
- App route cache: removed `glob()` + 12× `filemtime()` validation — simple `file_exists` check, cache valid until deleted
- Router: added string comparison (`$uri === $rPattern`) before regex for static routes — zero regex for `/`, `/info`, `/login`, etc.
- Gate: session no longer started for `/theme/*` asset requests — no `Set-Cookie` on CSS/JS
- Gate: `isEnabled()` simplified to `Config::get('CACHE_ENABLE', true) !== false` — removed redundant `require env.php`
- Gate: `matchNocache()` — `isset($map[$uri])` for O(1) static route lookup before regex loop
- Gate: `preRun()` — `matchNocache()` skipped for `/theme/*` asset requests (no session, no nocache check)

### Added
- View: module view lookup — `render()` checks `Modules/{Module}/Views/{view}.hbs` before falling back to theme directory
- App: built-in theme asset serving — `serveTheme()` handles `/theme/{theme}/{file}` for CSS/JS/images/fonts with ETag + 304 + 1 year cache
- SQLite driver — full `query()`, `first()`, `execute()`, transactions, WAL mode, foreign keys

### Changed
- DB drivers (MySQL, PostgreSQL): `connect()` no longer catches `PDOException` — exception propagates naturally to controller `try-catch` for graceful DB-unavailable handling

### Removed
- Gate: duplicate `Gate::init()` in `Boot::run()` — `Gate::enableFullErrors()` already sets all error handlers

## [0.9.0] — 2026-05-15

### Added
- Validation: 10 new rules — `max`, `alpha`, `integer`, `url`, `date`, `confirmed`, `same`, `different`, `regex`, `between`, `exists`; custom error messages via 3rd param; `fails()` alias for readability
- Pagination: `Pagination::query($sql, $params, $perPage)` — auto-count + auto-fetch in one call; result includes `data`, `from`, `to` meta
- Query: `where()` now supports operators (`->where('price', '>', 100)`); new methods: `whereNotIn`, `whereBetween`, `offset()`, `value()`, `pluck()`, `paginate()`, `increment()`, `decrement()`, `toSql()`
- Hooks: `add_hook($name, $callback)` and `run_hook($name, $data)` global functions — lightweight pub/sub pattern in `helpers.php`

### Changed
- Storage restructured: `storage/cache/{routes,nocache,*.cache}` → `cache/`; `storage/logs/` → `cache/logs/`; `storage/ratelimit/` → `cache/*.ratelimit` — cache/logs/ratelimit are now in `cache/` (safe to delete), `storage/` reserved for permanent data (`errors/`, `uploads/`)
- All framework paths updated: App, Cache, Gate, Log, RateLimiter
- Boot pipeline consolidated: `app/gate.php` + `app/bootstrap.php` → `src/Boot.php` (single entry point in framework); `index.php` now use class `Boot`

## [0.8.0] — 2026-05-13

### Added
- Response::error() — theme-aware custom error pages: renders `Themes/{theme}/errors/{code}.hbs` if exists, falls back to `Gate::errorPage()`

### Fixed
- View fallback path case mismatch: lowercase `themes/` to uppercase `Themes/`
- DB drivers: `APP_DEBUG` check never matched (`=== 'true'` against boolean `true`), DB error messages never shown
- Auth JWT: `base64UrlDecode()` padding calculation wrong (`str_pad` used total length instead of additional padding)
- Arr::first(): `reset($array) ?: $default` incorrectly returns default for valid falsy values (0, '', false)
- Router: escape dot in route patterns (`str_replace('.', '\\.', ...)`) — `/rss.xml` no longer matches `/rssXxml`

## [0.7.0] — 2026-05-12

### Changed
- View: `{{route}}`/`{{url}}` migrated from regex preprocessing to proper Handlebars helpers (`knownHelpers` + `helpers` array) — fully spec-compliant, partials inherit helpers from parent context automatically
- All templates: `{{if}}`/`{{endif}}` converted to standard Handlebars `{{#if}}`/`{{/if}}`; `{{if !x}}` → `{{#unless x}}`/`{{/unless}}`

### Removed
- View: regex preprocessing of `{{route}}`/`{{url}}` in both `render()` and `getPartials()`

## [0.6.0] — 2026-05-12

### Fixed
- View partials: `{{route}}`/`{{url}}` preprocessing added to `getPartials()` — partial templates now resolve URLs before Handlebars compilation, matching main template behavior

## [0.5.0] — 2026-05-12

### Changed
- View parser: replaced custom parser with `devtheorem/php-handlebars` (spec-compliant Handlebars implementation) — API surface unchanged, templates fully compatible

### Added
- Dependency: `devtheorem/php-handlebars ^2.0` (framework-level via Composer)

## [0.4.0] — 2026-05-10

### Fixed
- Model::query() visibility `protected` → `public`, signature aligned with `MySQL::query($sql, $params)` — resolves fatal error from inheritance chain `Model → DB → MySQL`
- Model::dbCatch() — catches `PDOException` on missing columns (`deleted_at`, `created_at`, `updated_at`) and shows helpful error with fix hint instead of raw 500

## [0.3.0] — 2026-05-10

### Added
- View parser upgrade: recursive nested `{{#each}}`, `{{@index}}`/`{{@key}}`, `{{@parent.*}}`/`{{@root.*}}` scope traversal
- `{{> partial}}` — load and render partials from `Themes/{theme}/partials/`
- `{{#with var}}...{{/with}}` — context scoping block

## [0.2.0] — 2026-05-10

### Added
- License (`MIT`) in `composer.json`
- App-scoped timezone via `APP_TIMEZONE` env config (`Env::load()` auto-sets `date_default_timezone_set()`)
- Timestamps (created_at, updated_at) auto-populate on create/update
- Soft delete with `deleted_at` column — enabled by default
- `whereNull()` and `whereNotNull()` in Query builder
- Pass-through methods: whereIn, whereLike, orderBy, limit
- `destroy($ids)` — soft/hard delete multiple records by primary key
- `withTrashed()` — include soft-deleted records in query
- `trashed()` — query only soft-deleted records
- `restore($id)` — un-delete a record
- `forceDeleteById($id)` — permanently delete regardless of soft delete

### Changed
- All read methods (find, all, where, count) auto-filter out soft-deleted rows
- `Model` extends `DB` — no external dependency change

## [0.1.0] — 2026-05-10

### Added
- Core framework classes: App, Auth, Cache, Config, DB, Env, Event, Flash, Log, Mail, Middleware, Model, Pagination, Query, RateLimiter, Request, Response, Router, Str, Upload, Validation, View
- Gate pre-router caching with ETag + 304 support
- Session-aware cache keys (tenant_user_uri based)
- Module system (self-contained Modules with Route/Controller/Model)
- Middleware support (auth, csrf, ratelimit, log, nocache)
- Named middleware groups in Config/middlewares.php
- CSRF protection helper
- Rate limiter (session + APCu)
- DB drivers: MySQL, PostgreSQL
- Str helpers: slug, camel, snake, kebab, studly, title, random, uuid, limit, words, plural, singular, mask, between, after, before, password, startsWith, endsWith, contains
- Cache drivers: file, APCu, Redis
- View rendering with Handlebars-like syntax

### Changed
- Response::html() stores body instead of direct echo
- App::run() returns Response::$body as string
- Gate pipeline extracted into Core\Gate class
- Request params reset on each dispatch

### Removed
- App hooks system ($hooks, register(), fire())

### Fixed
- CSRF token validation
- Session cookie security (httponly, samesite, strict mode)
- Global $_GET/$_POST sanitization removed (encode only at View output)