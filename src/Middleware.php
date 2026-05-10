<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Middleware {
    public static function run($middlewares) {
        foreach ($middlewares as $mw) {
            $param = null;
            $mwName = $mw;
            if (strpos($mw, ':') !== false) {
                $parts = explode(':', $mw, 2);
                $mwName = $parts[0];
                $param = $parts[1];
            }
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $mwName)) {
                Response::error(500, 'Invalid middleware name');
            }

            if ($mwName === 'nocache') {
                header('X-No-Cache: 1');
                continue;
            }

            $file = BASE_PATH . '/app/Middlewares/' . $mwName . '.php';
            if (!file_exists($file)) {
                Response::error(500, 'Middleware not found: ' . $mwName);
            }
            $result = require $file;
            if ($result === false) {
                return false;
            }
        }
        return true;
    }
}
