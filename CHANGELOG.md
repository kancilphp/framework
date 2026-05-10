# Changelog

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
