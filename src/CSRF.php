<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class CSRF {
    public static function generate() {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function field() {
        return '<input type="hidden" name="_csrf_token" value="' . self::generate() . '">';
    }

    public static function verify($token) {
        $ok = isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
        if ($ok) self::regenerate();
        return $ok;
    }

    protected static function regenerate() {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    public static function reset() {
        unset($_SESSION['_csrf_token']);
    }
}
