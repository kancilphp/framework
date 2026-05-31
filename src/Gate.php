<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;

class Gate
{
    public static $debug = false;
    public static $tenantPrefix = '';

    public static function init()
    {
        register_shutdown_function([static::class, 'handleShutdown']);
        set_exception_handler([static::class, 'handleException']);
    }

    public static function handleException($e)
    {
        static::errorPage(500, 'Internal Server Error', static::$debug ? $e->getMessage() : '');
    }

    public static function handleShutdown()
    {
        $e = error_get_last();
        if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            static::errorPage(500, 'Fatal Error', static::$debug ? $e['message'] : '');
        }
    }

    public static function errorPage($kode, $judul, $pesan = '', $debugBlock = '', $tracesBlock = '')
    {
        http_response_code($kode);
        header('Content-Type: text/html; charset=utf-8');
        $tpl = file_get_contents(BASE_PATH . '/storage/errors/error.php');
        $pesanHtml = $pesan ? '<div class="pesan">' . htmlspecialchars($pesan) . '</div>' : '';
        echo str_replace(
            ['{{kode}}', '{{judul}}', '{{pesan}}', '{{home_url}}', '{{debug_block}}', '{{traces_block}}'],
            [$kode, $judul, $pesanHtml, '/', $debugBlock, $tracesBlock],
            $tpl
        );
        exit;
    }

    public static function isEnabled()
    {
        return Config::get('CACHE_ENABLE', true) !== false;
    }

    public static function cacheKey($group, $userId, $prefix = '')
    {
        $path = $GLOBALS['_uri'];
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        $fullUri = $GLOBALS['_scheme'] . '://' . $GLOBALS['_host'] . $path . ($qs ? '?' . $qs : '');
        return "{$prefix}{$group}_{$userId}_" . md5($fullUri);
    }

    public static function cleanHeaders()
    {
        header_remove('Set-Cookie');
        header_remove('Pragma');
    }

    public static function serveETagAndExit($cached, $hash = null)
    {
        $etag = '"' . ($hash ?? md5($cached)) . '"';
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
            http_response_code(304);
            static::cleanHeaders();
            exit;
        }
        header("ETag: {$etag}");
        header("Cache-Control: private, must-revalidate");
        static::cleanHeaders();
        echo $cached . "\n<!-- Cache by 2811 ; 73 de Kancil -->";
        exit;
    }

    public static function setETag()
    {
        header("Cache-Control: private, must-revalidate");
        static::cleanHeaders();
    }

    public static function matchNocache($uri)
    {
        $dir = cacheDir();
        $nocacheFile = $dir ? $dir . '/nocache.php' : null;
        if (!$nocacheFile || !file_exists($nocacheFile)) return true;
        $map = require $nocacheFile;
        if (isset($map[$uri])) return $map[$uri];
        foreach ($map as $pattern => $isNocache) {
            if (strpos($pattern, ':') === false) continue;
            $regex = preg_replace('/:(\w+)/', '(?P<$1>[^/]+)', $pattern);
            if (preg_match('#^' . $regex . '$#', $uri)) return $isNocache;
        }
        return false;
    }

    public static function parseUrl()
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if ($base && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }
        $uri = $uri ?: '/';
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        $GLOBALS['_base'] = $base;
        $GLOBALS['_uri'] = $uri;
        $GLOBALS['_scheme'] = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $GLOBALS['_host'] = $_SERVER['HTTP_HOST'];

        return [$base, $uri];
    }

    public static function preRun($tenantPrefix = '')
    {
        static::$tenantPrefix = $tenantPrefix;

        [$base, $uri] = static::parseUrl();

        $method = $_SERVER['REQUEST_METHOD'];
        $isHead = ($method === 'HEAD');
        if ($isHead) {
            $method = 'GET';
            $_SERVER['REQUEST_METHOD'] = 'GET';
        }

        $seg = explode('/', trim($uri, '/'));
        $group = $seg[0] ?: 'home';

        $isAsset = preg_match('#^/theme/#', $uri);

        $hasNocache = false;
        if ($method === 'GET' && !$isAsset) {
            $hasNocache = static::matchNocache($uri);
        }

        $userId = 'guest';
        if (session_status() === PHP_SESSION_NONE && !$isAsset && !empty($_COOKIE[session_name()])) {
            session_cache_limiter('');
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => false,
                'cookie_samesite' => 'Strict',
                'use_strict_mode' => true,
            ]);
            $userId = Session::get('user')['id'] ?? 'guest';
        }

        $cacheEnabled = static::isEnabled();
        $cacheKey = static::cacheKey($group, $userId, static::$tenantPrefix);

        if ($method === 'GET' && !$hasNocache && $cacheEnabled) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                static::serveETagAndExit($cached, Cache::$lastHash);
            }
        }

        return [
            'method' => $method,
            'isHead' => $isHead,
            'hasNocache' => $hasNocache,
            'cacheEnabled' => $cacheEnabled,
            'cacheKey' => $cacheKey,
            'group' => $group,
        ];
    }

    public static function finish($ctx, $output)
    {
        $method = $ctx['method'];
        $isHead = $ctx['isHead'];
        $hasNocache = $ctx['hasNocache'];
        $cacheEnabled = $ctx['cacheEnabled'];
        $cacheKey = $ctx['cacheKey'];
        $group = $ctx['group'];

        $responseCode = http_response_code();
        $ok = $responseCode === false || ($responseCode >= 200 && $responseCode < 300);

        if ($method === 'GET' && !$hasNocache && $ok && $cacheEnabled) {
            Cache::set($cacheKey, $output, Config::get('CACHE_TTL', 10800));
            header("ETag: \"" . md5($output) . '"');
            static::setETag();
        }

        if ($method !== 'GET' && $ok && $cacheEnabled) {
            Cache::flushByPrefix(static::$tenantPrefix . $group . '_');
        }

        if (!cacheDir() && strpos($output, '<body') !== false) {
            $tip = '<div style="background:#fbbf24;color:#000;text-align:center;padding:3px 12px;font:13px/1.4 sans-serif">⚠️ Buat folder <code>cache/</code> dan <code>chmod 0777</code></div>';
            $output = preg_replace('/<body[^>]*>/', '$0' . $tip, $output);
        }

        if (!$isHead) echo $output; 
    }

    public static function enableFullErrors()
    {
        set_error_handler(function ($severity, $msg, $file, $line) {
            if (!(error_reporting() & $severity)) return false;
            throw new \ErrorException($msg, 0, $severity, $file, $line);
        });

        set_exception_handler(function ($e) {
            $debug = Config::get('APP_DEBUG', false);
            $pesan = $debug ? $e->getMessage() : '';
            $dbg = $debug ? '<hr><div class="debug-box"><pre>' . htmlspecialchars($e->getMessage()) . '</pre></div>' : '';
            $trc = $debug ? '<hr><div class="debug-box"><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre></div>' : '';
            static::errorPage(500, 'Internal Server Error', $pesan, $dbg, $trc);
        });

        register_shutdown_function(function () {
            $e = error_get_last();
            if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $debug = Config::get('APP_DEBUG', false);
                $pesan = $debug ? $e['message'] : '';
                $dbg = $debug ? '<hr><div class="debug-box"><pre>' . htmlspecialchars($e['message']) . '</pre></div>' : '';
                $trc = $debug ? '<hr><div class="debug-box"><pre>' . htmlspecialchars($e['file'] . ':' . $e['line']) . '</pre></div>' : '';
                static::errorPage(500, 'Fatal Error', $pesan, $dbg, $trc);
            }
        });
    }
}
