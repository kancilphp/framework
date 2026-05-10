<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Str {
    public static function slug($string, $separator = '-') {
        $string = preg_replace('/[^\pL\pN]+/u', $separator, $string);
        $string = trim($string, $separator);
        return strtolower($string);
    }

    public static function camel($string) {
        $string = preg_replace('/[^a-zA-Z0-9]+/', ' ', $string);
        return lcfirst(str_replace(' ', '', ucwords(trim($string))));
    }

    public static function snake($string, $separator = '_') {
        $string = preg_replace('/([a-z])([A-Z])/', '$1' . $separator . '$2', $string);
        $string = preg_replace('/[^a-zA-Z0-9]+/', $separator, $string);
        return strtolower(trim($string, $separator));
    }

    public static function kebab($string) {
        return self::snake($string, '-');
    }

    public static function studly($string) {
        return ucfirst(self::camel($string));
    }

    public static function title($string) {
        return ucwords(strtolower($string));
    }

    public static function random($length = 16, $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
        $string = '';
        $max = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $string .= $alphabet[random_int(0, $max)];
        }
        return $string;
    }

    public static function uuid() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public static function uuidV7() {
        $ms = (int) (microtime(true) * 1000);
        return sprintf(
            '%08x-%04x-7%03x-%04x-%04x%08x',
            ($ms >> 16) & 0xFFFFFFFF,
            $ms & 0xFFFF,
            random_int(0, 0xFFF),
            random_int(0, 0x3FFF) | 0x8000,
            random_int(0, 0xFFFF),
            random_int(0, 0xFFFFFFFF)
        );
    }

    public static function limit($string, $limit = 100, $end = '...') {
        if (mb_strlen($string) <= $limit) return $string;
        return rtrim(mb_substr($string, 0, max(0, $limit - mb_strlen($end)))) . $end;
    }

    public static function excerpt($string, $length = 100) {
        $string = strip_tags($string);
        return self::limit($string, $length);
    }

    public static function words($string, $limit = 10, $end = '...') {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $limit . '}/u', $string, $matches);
        if (!isset($matches[0]) || strlen($string) === strlen($matches[0])) return $string;
        return rtrim($matches[0]) . $end;
    }

    public static function plural($word) {
        $irregular = [
            'child' => 'children', 'person' => 'people', 'man' => 'men',
            'woman' => 'women', 'mouse' => 'mice', 'foot' => 'feet',
            'goose' => 'geese', 'tooth' => 'teeth', 'ox' => 'oxen',
        ];
        $lower = strtolower($word);
        if (isset($irregular[$lower])) return $irregular[$lower];
        if (preg_match('/(s|x|z|ch|sh)$/', $word)) return $word . 'es';
        if (preg_match('/[^aeiou]y$/', $word)) return substr($word, 0, -1) . 'ies';
        if (preg_match('/(f|fe)$/', $word)) return preg_replace('/(f|fe)$/', 'ves', $word);
        return $word . 's';
    }

    public static function singular($word) {
        $irregular = [
            'children' => 'child', 'people' => 'person', 'men' => 'man',
            'women' => 'woman', 'mice' => 'mouse', 'feet' => 'foot',
            'geese' => 'goose', 'teeth' => 'tooth', 'oxen' => 'ox',
        ];
        $lower = strtolower($word);
        if (isset($irregular[$lower])) return $irregular[$lower];
        if (preg_match('/(ch|sh|s|x|z|ses)es$/', $word)) return substr($word, 0, -2);
        if (preg_match('/[^aeiou]ies$/', $word)) return substr($word, 0, -3) . 'y';
        if (preg_match('/ves$/', $word)) return preg_replace('/ves$/', '', $word) . 'f';
        if (preg_match('/s$/', $word) && !preg_match('/ss$/', $word)) return substr($word, 0, -1);
        return $word;
    }

    public static function startsWith($haystack, $needle) {
        return str_starts_with($haystack, $needle);
    }

    public static function endsWith($haystack, $needle) {
        return str_ends_with($haystack, $needle);
    }

    public static function contains($haystack, $needle) {
        return str_contains($haystack, $needle);
    }

    public static function mask($string, $char = '*', $first = 4, $last = 4) {
        $len = strlen($string);
        if ($len <= $first + $last) return $string;
        return substr($string, 0, $first) . str_repeat($char, $len - $first - $last) . substr($string, -$last);
    }

    public static function between($string, $start, $end) {
        $startPos = strpos($string, $start);
        if ($startPos === false) return '';
        $startPos += strlen($start);
        $endPos = strpos($string, $end, $startPos);
        if ($endPos === false) return substr($string, $startPos);
        return substr($string, $startPos, $endPos - $startPos);
    }

    public static function after($string, $search) {
        $pos = strpos($string, $search);
        if ($pos === false) return $string;
        return substr($string, $pos + strlen($search));
    }

    public static function before($string, $search) {
        $pos = strpos($string, $search);
        return $pos === false ? $string : substr($string, 0, $pos);
    }

    public static function ex($string, $fallback = '') {
        return trim($string) !== '' ? $string : $fallback;
    }

    public static function inline($string) {
        return preg_replace('/\s+/', ' ', trim($string));
    }



    public static function password($length = 16) {
        $sets = [
            '0123456789',
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '!@#$%^&*()_+-=',
        ];
        $password = '';
        foreach ($sets as $set) {
            $password .= $set[random_int(0, strlen($set) - 1)];
        }
        $all = implode('', $sets);
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }
        return str_shuffle($password);
    }
}
