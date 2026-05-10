<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class RateLimiter {
    public static function exceeded($key, $maxAttempts = 5, $window = 300) {
        $data = self::getData($key);
        if (!$data) return false;
        if (time() - $data['time'] > $window) return false;
        return $data['count'] >= $maxAttempts;
    }

    public static function increment($key) {
        $data = self::getData($key);
        if (!$data || time() - $data['time'] > 300) {
            $data = ['count' => 0, 'time' => time()];
        }
        $data['count']++;
        self::setData($key, $data);
    }

    public static function reset($key) {
        self::clearData($key);
    }

    public static function remainingTime($key, $window = 300) {
        $data = self::getData($key);
        if (!$data) return 0;
        return max(0, ceil(($window - (time() - $data['time'])) / 60));
    }

    public static function ipKey($action) {
        return 'ratelimit:' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . ':' . $action;
    }

    protected static function getData($key) {
        if (function_exists('apcu_get') && apcu_exists($key)) {
            return apcu_get($key);
        }
        $path = self::path($key);
        if (!file_exists($path)) return null;
        return unserialize(file_get_contents($path));
    }

    protected static function setData($key, $data) {
        if (function_exists('apcu_store')) {
            apcu_store($key, $data, 3600);
        }
        $path = self::path($key);
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents($path, serialize($data), LOCK_EX);
    }

    protected static function clearData($key) {
        if (function_exists('apcu_delete') && apcu_exists($key)) {
            apcu_delete($key);
        }
        $path = self::path($key);
        if (file_exists($path)) unlink($path);
    }

    protected static function path($key) {
        return BASE_PATH . '/storage/cache/' . md5($key) . '.ratelimit';
    }
}
