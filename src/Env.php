<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Env {
    public static function load($path = null) {
        $path = $path ?: BASE_PATH . '/env.php';
        if (!file_exists($path)) return;
        $values = require $path;
        foreach ($values as $key => $value) {
            Config::set($key, $value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}
