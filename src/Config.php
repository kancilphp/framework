<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Config {
    public static $data = [];

    protected static function normalizeValue($value) {
        if (!is_string($value)) return $value;
        $v = strtolower($value);
        if (in_array($v, ['1', 'on', 'true', 'yes', 'enable'], true)) return true;
        if (in_array($v, ['0', 'off', 'false', 'no', 'disable'], true)) return false;
        if (is_numeric($value)) return $value + 0;
        return $value;
    }

    public static function set($key, $value) {
        self::$data[$key] = self::normalizeValue($value);
    }

    public static function get($key, $default = null) {
        return isset(self::$data[$key]) ? self::$data[$key] : $default;
    }
}
