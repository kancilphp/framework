<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Request {
    public static $params = [];
    public static $tenant = '';

    public static function tenant() {
        return self::$tenant;
    }

    public static function setParams($params) {
        self::$params = $params;
    }

    public static function param($key, $default = null) {
        $value = isset(self::$params[$key]) ? self::$params[$key] : $default;
        return self::sanitize($value);
    }

    public static function input($key, $default = null) {
        $value = isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
        return self::sanitize($value);
    }

    public static function all() {
        return $_REQUEST;
    }

    public static function only($keys) {
        $result = [];
        foreach ($keys as $key) {
            if (isset($_REQUEST[$key])) {
                $result[$key] = $_REQUEST[$key];
            }
        }
        return $result;
    }

    public static function has($key) {
        return isset($_REQUEST[$key]);
    }

    public static function method() {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function uri() {
        return REQUEST_URI_CLEAN;
    }

    public static function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    protected static function sanitize($value) {
        if (!is_string($value)) return $value;
        return trim($value);
    }
}
