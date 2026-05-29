<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Cache {
    protected static $redis = null;
    public static $lastHash = null;

    protected static function driver() {
        $driver = Config::get('CACHE_DRIVER', 'file');
        if ($driver === 'apcu' && !function_exists('apcu_store')) return 'file';
        if ($driver === 'redis' && !class_exists('\Redis')) return 'file';
        return $driver;
    }

    public static function filePath($key) {
        $dir = cacheDir();
        return $dir ? $dir . '/' . $key . '.cache' : null;
    }

    public static function enabled() {
        return Config::get('CACHE_ENABLE', true) !== false;
    }

    public static function get($key) {
        if (!self::enabled()) return null;
        $driver = self::driver();
        if ($driver === 'apcu') {
            $val = apcu_fetch(self::prefix($key));
            return $val === false ? null : $val;
        }
        if ($driver === 'redis') {
            $redis = self::redis();
            if (!$redis) return null;
            $data = $redis->get(self::prefix($key));
            return $data !== false ? $data : null;
        }
        $path = self::filePath($key);
        if (!$path || !file_exists($path)) return null;
        $content = file_get_contents($path);
        if (!$content) return null;
        $pos = strpos($content, "\n");
        if ($pos === false) return null;
        $header = substr($content, 0, $pos);
        [$expires, $hash] = explode(':', $header, 2);
        if ((int)$expires < time()) {
            @unlink($path);
            return null;
        }
        self::$lastHash = $hash;
        return substr($content, $pos + 1);
    }

    public static function set($key, $value, $ttl = 60) {
        if (!self::enabled()) return;
        $driver = self::driver();
        if ($driver === 'apcu') {
            apcu_store(self::prefix($key), $value, $ttl);
            return;
        }
        if ($driver === 'redis') {
            $redis = self::redis();
            if (!$redis) return;
            $redis->setex(self::prefix($key), $ttl, $value);
            return;
        }
        $path = self::filePath($key);
        if (!$path) return;
        $expires = time() + $ttl;
        $hash = md5($value);
        $tmp = $path . '.tmp';
        file_put_contents($tmp, "{$expires}:{$hash}\n{$value}", LOCK_EX);
        rename($tmp, $path);
    }

    public static function remember($key, $ttl, $callback) {
        if (!self::enabled()) return $callback();
        $value = self::get($key);
        if ($value !== null) return $value;
        $value = $callback();
        self::set($key, $value, $ttl);
        return $value;
    }

    public static function delete($key) {
        if (!self::enabled()) return;
        $driver = self::driver();
        if ($driver === 'apcu') {
            apcu_delete(self::prefix($key));
            return;
        }
        if ($driver === 'redis') {
            $redis = self::redis();
            if (!$redis) return;
            $redis->del(self::prefix($key));
            return;
        }
        $path = self::filePath($key);
        if ($path && file_exists($path)) unlink($path);
    }

    public static function clear() {
        if (!self::enabled()) return;
        $driver = self::driver();
        if ($driver === 'apcu') {
            apcu_clear();
            return;
        }
        if ($driver === 'redis') {
            $redis = self::redis();
            if (!$redis) return;
            $keys = $redis->keys(self::prefix() . '*');
            if (!empty($keys)) {
                $redis->del($keys);
            }
            return;
        }
        $dir = cacheDir();
        if (!$dir) return;
        $files = glob($dir . '/*.cache');
        foreach ($files as $file) unlink($file);
    }

    public static function flushByPrefix($prefix) {
        if (!self::enabled()) return;
        $driver = self::driver();
        if ($driver === 'apcu') {
            $info = apcu_cache_info(true);
            if (!$info || empty($info['cache_list'])) return;
            foreach ($info['cache_list'] as $entry) {
                if (strpos($entry['info'], self::prefix() . $prefix) === 0) {
                    apcu_delete($entry['info']);
                }
            }
            return;
        }
        if ($driver === 'redis') {
            $redis = self::redis();
            if (!$redis) return;
            $keys = $redis->keys(self::prefix() . $prefix . '*');
            if (!empty($keys)) {
                $redis->del($keys);
            }
            return;
        }
        $dir = cacheDir();
        if (!$dir) return;
        $files = glob($dir . '/' . "{$prefix}*.cache");
        if ($files) {
            foreach ($files as $f) @unlink($f);
        }
    }

    public static function flushNginx($zone = 'kancil') {
        if (!self::enabled()) return;
        $version = self::get('__nginx_version__' . $zone) ?: 0;
        self::set('__nginx_version__' . $zone, $version + 1, 31536000);
    }

    public static function getVersion($zone = 'kancil') {
        return self::get('__nginx_version__' . $zone) ?: 0;
    }

    protected static function prefix($key = null) {
        $p = Config::get('CACHE_PREFIX', 'kancil_');
        return $key === null ? $p : $p . $key;
    }

    protected static function redis() {
        if (self::$redis !== null) return self::$redis;
        $host = Config::get('REDIS_HOST', '127.0.0.1');
        $port = Config::get('REDIS_PORT', 6379);
        try {
            self::$redis = new \Redis();
            self::$redis->pconnect($host, $port, 2.5);
        } catch (\Throwable $e) {
            self::$redis = null;
            return null;
        }
        return self::$redis;
    }
}
