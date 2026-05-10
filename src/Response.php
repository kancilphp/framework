<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Response {
    public static $body = '';

    public static function json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public static function success($data = null, $code = 200) {
        return self::json(['status' => 'success', 'data' => $data], $code);
    }

    public static function fail($message, $code = 400, $errors = []) {
        return self::json(['status' => 'fail', 'message' => $message, 'errors' => $errors], $code);
    }

    public static function html($content) {
        header('Content-Type: text/html; charset=utf-8');
        self::$body = $content;
    }

    public static $afterRun = [];

    public static function afterRun($fn) {
        self::$afterRun[] = $fn;
    }

    public static function filterBody($fn) {
        self::$body = $fn(self::$body);
    }

    public static function error($code, $message = '') {
        $errors = [
            400 => 'Bad Request',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
        ];
        $title = $errors[$code] ?? 'Error';
        $debug = Config::get('APP_DEBUG', false) ? $message : '';
        $dbg = $debug ? '<hr><div class="debug-box"><pre>' . htmlspecialchars($message) . '</pre></div>' : '';
        Gate::errorPage($code, $title, $message, $dbg);
    }

    public static function redirect($url) {
        header('Location: ' . $url);
    }
}
