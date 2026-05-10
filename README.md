# KancilPHP Framework

KISS, zero-dependency PHP framework with pre-router caching.

## Features

- **Pre-router cache** — cache check before framework bootstrap, ETag + 304 support
- **Session-aware** — cache keys include user ID, no stale user data served
- **Module system** — self-contained Modules with Route/Controller/Model
- **Middleware** — per-route middleware with named groups
- **No bloat** — no service container, no facade, no artisan

## Requirements

- PHP >= 8.3

## Install

```bash
composer require kancilphp/framework
```

## Structure

```
src/
├── App.php              → Route resolver + dispatcher
├── Auth.php             → JWT + password-based auth
├── Cache.php            → File / APCu / Redis
├── Config.php           → Simple key-value config
├── DB.php               → PDO wrapper with query builder
├── Gate.php             → Pre-router pipeline (error, session, cache)
├── Middleware.php       → Middleware runner
├── Request.php          → Input, params, method
├── Response.php         → JSON, HTML, redirect, error pages
├── Router.php           → URI matching + dispatch
├── Str.php              → String helpers
├── View.php             → Template rendering
├── Drivers/
│   ├── MySQL.php
│   └── PostgreSQL.php
└── helpers.php          → Global helpers (csrf, url, env, base64)
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT
