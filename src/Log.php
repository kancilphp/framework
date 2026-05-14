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
            $dir = cacheDir('logs/');
            self::$path = $dir ? $dir . '/' . date('Y-m-d') . '.log' : null;
        }
        return self::$path;
    }

    public static function write($level, $message, array $context = []) {
        $ts = date('Y-m-d H:i:s');
        $ctx = empty($context) ? '' : ' ' . json_encode($context);
        $line = "[{$ts}] [{$level}] {$message}{$ctx}";
        $path = self::path();
        if ($path) {
            file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else {
            error_log($line);
        }
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
