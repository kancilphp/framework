# Changelog

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
