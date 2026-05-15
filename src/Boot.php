<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;

class Boot {
    public static function run() {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        $__env = require BASE_PATH . '/env.php';
        Config::set('APP_DEBUG', $__env['APP_DEBUG'] ?? false);
        Config::set('CACHE_ENABLE', $__env['CACHE_ENABLE'] ?? true);
        Config::set('CACHE_DRIVER', $__env['CACHE_DRIVER'] ?? 'file');
        Config::set('CACHE_PREFIX', $__env['CACHE_PREFIX'] ?? 'kancil_');
        Gate::$debug = Config::get('APP_DEBUG', false);

        $tenantPrefix = '';
        $hookFile = BASE_PATH . '/app/Hooks/tenant.php';
        if (file_exists($hookFile)) {
            $t = (require $hookFile)();
            if (is_string($t) && $t !== '') {
                $tenantPrefix = $t . '_';
                Request::$tenant = $t;
            }
        }

        Gate::init();
        $ctx = Gate::preRun($tenantPrefix);

        Env::load();
        $driver = Config::get('DB_DRIVER', 'mysql');
        $drivers = [
            'mysql' => 'Core\\Drivers\\MySQL',
            'pgsql' => 'Core\\Drivers\\PostgreSQL',
            'sqlite' => 'Core\\Drivers\\SQLite',
        ];
        $driverClass = $drivers[$driver] ?? $drivers['mysql'];
        if (class_exists($driverClass)) {
            class_alias($driverClass, 'Core\\DatabaseDriver');
        }

        foreach (glob(BASE_PATH . '/app/Helpers/*.php') as $f) {
            require $f;
        }

        Gate::enableFullErrors();

        $appHooks = BASE_PATH . '/app/Config/hooks.php';
        if (file_exists($appHooks)) {
            foreach (require $appHooks as $hook => $listeners) {
                foreach ($listeners as $listener) {
                    add_hook($hook, $listener);
                }
            }
        }

        define('BASE_URL', $GLOBALS['_scheme'] . '://' . $GLOBALS['_host'] . $GLOBALS['_base']);
        define('REQUEST_URI_CLEAN', $GLOBALS['_uri']);

        $output = App::run();
        foreach (Response::$afterRun as $fn) $output = $fn($output);
        Gate::finish($ctx, $output);
    }
}
