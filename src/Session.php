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
        return ($_SESSION ?? [])[$k] ?? $default;
    }

    public static function set($key, $value) {
        if (($_SESSION ?? null) === null) {
            @session_start();
        }
        $_SESSION[self::prefix() . $key] = $value;
    }

    public static function unset($key) {
        $s = $_SESSION ?? null;
        if ($s !== null) {
            unset($s[self::prefix() . $key]);
            $_SESSION = $s;
        }
    }

    public static function has($key) {
        return isset(($_SESSION ?? [])[self::prefix() . $key]);
    }

    public static function clear() {
        $prefix = self::prefix();
        $s = $_SESSION ?? null;
        if ($s === null) return;
        foreach ($s as $k => $v) {
            if (strpos($k, $prefix) === 0) unset($s[$k]);
        }
        $_SESSION = $s;
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

    public static function csrfToken() {
        $k = self::prefix() . '_csrf_token';
        if (empty($_SESSION[$k])) {
            $_SESSION[$k] = bin2hex(random_bytes(32));
        }
        return $_SESSION[$k];
    }

    public static function csrfVerify($token) {
        $k = self::prefix() . '_csrf_token';
        if (empty($_SESSION[$k]) || empty($token)) return false;
        return hash_equals($_SESSION[$k], $token);
    }
}
