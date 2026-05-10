<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Flash {
    public static function set($type, $message) {
        $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
    }

    public static function get() {
        $flash = $_SESSION['_flash'] ?? null;
        unset($_SESSION['_flash']);
        return $flash;
    }

    public static function has() {
        return isset($_SESSION['_flash']);
    }
}
