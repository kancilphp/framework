<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;

class Session {

    protected static function prefix() {
        $tid = Request::$tenant ?: '0';
        return $tid . '_';
    }

    public static function get($key, $default = null) {
        $k = self::prefix() . $key;
        return array_key_exists($k, $_SESSION) ? $_SESSION[$k] : $default;
    }

    public static function set($key, $value) {
        $_SESSION[self::prefix() . $key] = $value;
    }

    public static function unset($key) {
        unset($_SESSION[self::prefix() . $key]);
    }

    public static function has($key) {
        return array_key_exists(self::prefix() . $key, $_SESSION);
    }

    public static function clear() {
        $prefix = self::prefix();
        foreach ($_SESSION as $k => $v) {
            if (strpos($k, $prefix) === 0) unset($_SESSION[$k]);
        }
    }

    public static function flash($message) {
        self::set('_flash', $message);
    }

    public static function flashError($message) {
        self::set('_flash_error', $message);
    }

    public static function flashGet() {
        $msg = self::get('_flash');
        if ($msg !== null) self::unset('_flash');
        return $msg;
    }

    public static function flashErrorGet() {
        $msg = self::get('_flash_error');
        if ($msg !== null) self::unset('_flash_error');
        return $msg;
    }

    public static function login($user) {
        session_regenerate_id(true);
        self::set('user', $user);
    }

    public static function user() {
        return self::get('user');
    }

    public static function logout() {
        self::unset('user');
        session_regenerate_id(true);
    }

    public static function cart() {
        return self::get('cart', []);
    }

    public static function cartSet($items) {
        self::set('cart', $items);
    }

    public static function cartClear() {
        self::unset('cart');
    }
}
