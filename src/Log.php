<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Log {
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';

    private static $path;

    private static function path() {
        if (!self::$path) {
            self::$path = BASE_PATH . '/storage/logs/' . date('Y-m-d') . '.log';
            $dir = dirname(self::$path);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
        }
        return self::$path;
    }

    public static function write($level, $message, array $context = []) {
        $ts = date('Y-m-d H:i:s');
        $ctx = empty($context) ? '' : ' ' . json_encode($context);
        $line = "[{$ts}] [{$level}] {$message}{$ctx}" . PHP_EOL;
        file_put_contents(self::path(), $line, FILE_APPEND | LOCK_EX);
    }

    public static function info($message, array $context = []) {
        self::write(self::INFO, $message, $context);
    }

    public static function warning($message, array $context = []) {
        self::write(self::WARNING, $message, $context);
    }

    public static function error($message, array $context = []) {
        self::write(self::ERROR, $message, $context);
    }
}
