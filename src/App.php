<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;

class App {
    public static function run() {
        $uri = REQUEST_URI_CLEAN;

        if (preg_match('#^/theme/([a-zA-Z0-9_-]+)/(.+)$#', $uri, $m)) {
            self::serveTheme($m[1], $m[2]);
        }

        $router = new Router();
        $routes = self::loadRoutes();
        foreach ($routes as $route) {
            $router->addRoutes([$route]);
        }

        $method = Request::method();
        $router->dispatch($method, $uri);

        return Response::$body;
    }

    protected static function serveTheme($theme, $file) {
        if (!preg_match('/^[a-zA-Z0-9_\/-]+\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/', $file)) {
            return;
        }
        $path = BASE_PATH . '/app/Themes/' . $theme . '/' . $file;
        if (!file_exists($path)) {
            return;
        }
        $mtime = filemtime($path);
        $etag = '"' . md5($path . $mtime) . '"';
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
            header('HTTP/1.1 304 Not Modified');
            exit;
        }
        $mimes = [
            'css' => 'text/css', 'js' => 'application/javascript',
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
            'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
        ];
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream') . '; charset=utf-8');
        header('Cache-Control: public, max-age=31536000, immutable');
        header('ETag: ' . $etag);
        readfile($path);
        exit;
    }

    protected static function loadRoutes() {
        $cacheDir = cacheDir();
        $cacheFile = $cacheDir ? $cacheDir . '/routes.php' : null;

        if ($cacheFile && file_exists($cacheFile)) {
            return require $cacheFile;
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
