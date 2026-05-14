<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

use Core\Arr;
use Core\Config;
use Core\CSRF;
use Core\HttpClient;
use Core\Str;

function csrfToken() {
    return CSRF::generate();
}

function csrfField() {
    return CSRF::field();
}

function url($path = '/') {
    return BASE_URL . '/' . ltrim($path, '/');
}

function route($path) {
    return url($path);
}

function env($key, $default = null) {
    return Config::get($key, $default);
}

function value($value, ...$args) {
    return $value instanceof Closure ? $value(...$args) : $value;
}

function str($string = '') {
    return new Str($string);
}

function http($url = null) {
    return HttpClient::make($url);
}

function arr($array = []) {
    return new Arr($array);
}

function retry($times, $callback, $sleep = 0) {
    for ($i = 0; $i < $times; $i++) {
        try {
            return $callback();
        } catch (\Exception $e) {
            if ($i === $times - 1) throw $e;
            if ($sleep > 0) usleep($sleep * 1000);
        }
    }
}

function cacheDir($sub = '') {
    $dir = BASE_PATH . '/cache/' . ltrim($sub, '/');
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @chmod($dir, 0777);
    if (!is_dir($dir) || !is_writable($dir)) return null;
    return rtrim($dir, '/') . '/';
}

function tap($value, $callback = null) {
    if ($callback === null) return new class($value) {
        public function __construct(public $value) {}
        public function __call($method, $args) { $this->value->$method(...$args); return $this; }
    };
    $callback($value);
    return $value;
}

$GLOBALS['_hooks'] = [];

function add_hook($hook, $callback) {
    $GLOBALS['_hooks'][$hook][] = $callback;
}

function run_hook($hook, $data = []) {
    foreach ($GLOBALS['_hooks'][$hook] ?? [] as $callback) {
        call_user_func($callback, $data);
    }
}
