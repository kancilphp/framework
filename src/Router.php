<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;

use ReflectionMethod;

class Router {
    protected $routes = [];

    public function load($file) {
        $this->routes = require $file;
    }

    public function addRoutes(array $routes) {
        $this->routes = array_merge($this->routes, $routes);
    }

    public function dispatch($method, $uri) {
        Request::$params = [];
        foreach ($this->routes as $route) {
            $rMethod = $route[0];
            $rPattern = $route[1];
            $rHandler = $route[2];
            $rMiddleware = $route[3] ?? [];

            if ($rMethod !== $method) continue;

            $regex = str_replace('.', '\\.', $rPattern);
            $regex = preg_replace('/:(\w+)/', '(?P<$1>[^/]+)', $regex);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                Request::setParams($params);

                if (!Middleware::run($rMiddleware)) {
                    return;
                }

                self::callHandler($rHandler);
                return;
            }
        }
        Response::error(404, 'Not Found');
    }

    protected static function callHandler($handler) {
        if (strpos($handler, '@') !== false) {
            [$class, $method] = explode('@', $handler);
            if (!class_exists($class)) {
                Response::error(500, 'Controller not found: ' . $class);
            }
            $ctrl = new $class();
            $ctrl->$method();
        }
    }
}
