<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;

class App {
    public static function run() {
        $router = new Router();
        $routes = self::loadRoutes();
        foreach ($routes as $route) {
            $router->addRoutes([$route]);
        }

        $method = Request::method();
        $uri = REQUEST_URI_CLEAN;

        $router->dispatch($method, $uri);

        return Response::$body;
    }

    protected static function loadRoutes() {
        $cacheDir = cacheDir();
        $cacheFile = $cacheDir ? $cacheDir . '/routes.php' : null;

        if ($cacheFile) {
            $cacheTime = file_exists($cacheFile) ? filemtime($cacheFile) : 0;
            $latestMtime = 0;
            foreach (glob(BASE_PATH . '/app/Modules/*/Route.php') as $f) {
                $latestMtime = max($latestMtime, filemtime($f));
            }
            if ($cacheTime > 0 && $cacheTime >= $latestMtime) {
                return require $cacheFile;
            }
        }

        $groups = [];
        $groupsFile = BASE_PATH . '/app/Config/middlewares.php';
        if (file_exists($groupsFile)) {
            $groups = require $groupsFile;
        }

        $routes = [];
        foreach (glob(BASE_PATH . '/app/Modules/*/Route.php') as $f) {
            $moduleRoutes = require $f;
            $moduleMw = [];
            if (isset($moduleRoutes['middleware'])) {
                $moduleMw = (array)$moduleRoutes['middleware'];
                unset($moduleRoutes['middleware']);
            }
            $prefix = basename(dirname($f));
            $urlPrefix = $prefix === 'Home' ? '' : strtolower($prefix);
            $ns = "Modules\\{$prefix}\\";
            foreach ($moduleRoutes as $route) {
                $path = $route[1];
                if ($urlPrefix !== '') {
                    $path = $path === '/' ? "/{$urlPrefix}" : "/{$urlPrefix}{$path}";
                }
                $route[1] = $path;
                $route[2] = $ns . $route[2];
                $mw = $route[3] ?? [];
                if (is_string($mw)) $mw = $groups[$mw] ?? [$mw];
                $route[3] = array_merge($moduleMw, $mw);
                $routes[] = $route;
            }
        }

        if ($cacheFile) {
            file_put_contents($cacheFile, '<?php return ' . var_export($routes, true) . ';');

            // Write nocache map for gate.php
            $nocacheFile = $cacheDir . '/nocache.php';
            $map = [];
            foreach ($routes as $r) {
                if ($r[0] !== 'GET') continue;
                $has = false;
                foreach ($r[3] ?? [] as $mw) {
                    if (strpos($mw, 'nocache') !== false) { $has = true; break; }
                }
                $map[$r[1]] = $has;
            }
            file_put_contents($nocacheFile, '<?php return ' . var_export($map, true) . ';');
        }

        return $routes;
    }
}
