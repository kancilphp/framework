<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Auth {
    public static function login($user) {
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
    }

    public static function user() {
        if (isset($_SESSION['user'])) return $_SESSION['user'];
        return self::userFromToken();
    }

    public static function check() {
        return self::user() !== null;
    }

    public static function logout() {
        session_regenerate_id(true);
        unset($_SESSION['user']);
    }

    public static function generate($user) {
        $header = self::base64Url(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = self::base64Url(json_encode(array_merge($user, [
            'exp' => time() + Config::get('JWT_EXPIRY', 86400),
        ])));
        $secret = Config::get('JWT_SECRET', 'secret');
        $signature = self::base64Url(hash_hmac('sha256', "$header.$payload", $secret, true));
        return "$header.$payload.$signature";
    }

    public static function userFromToken() {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        if (!$header) return null;
        $parts = explode(' ', $header);
        if (count($parts) !== 2) return null;
        $token = $parts[1];
        $segments = explode('.', $token);
        if (count($segments) !== 3) return null;
        $secret = Config::get('JWT_SECRET', 'secret');
        $expected = self::base64Url(hash_hmac('sha256', "$segments[0].$segments[1]", $secret, true));
        if (!hash_equals($expected, $segments[2])) return null;
        $user = json_decode(self::base64UrlDecode($segments[1]), true);
        if (!isset($user['exp']) || $user['exp'] < time()) return null;
        return $user;
    }

    protected static function base64Url($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected static function base64UrlDecode($data) {
        $data = strtr($data, '-_', '+/');
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode($data);
    }
}
