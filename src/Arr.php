<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Arr {
    public static function get($array, $key, $default = null) {
        if ($key === null) return $array;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return value($default);
            }
            $array = $array[$segment];
        }
        return $array;
    }

    public static function set(&$array, $key, $value) {
        if ($key === null) {
            $array = $value;
            return;
        }
        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $segment = array_shift($keys);
            if (!isset($array[$segment]) || !is_array($array[$segment])) {
                $array[$segment] = [];
            }
            $array = &$array[$segment];
        }
        $array[array_shift($keys)] = $value;
    }

    public static function has($array, $key) {
        if (empty($array) || $key === null) return false;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) return false;
            $array = $array[$segment];
        }
        return true;
    }

    public static function unset(&$array, $key) {
        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $segment = array_shift($keys);
            if (!isset($array[$segment]) || !is_array($array[$segment])) return;
            $array = &$array[$segment];
        }
        unset($array[array_shift($keys)]);
    }

    public static function first($array, $callback = null, $default = null) {
        if ($callback === null) return reset($array) ?: $default;
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) return $value;
        }
        return $default;
    }

    public static function last($array, $callback = null, $default = null) {
        if ($callback === null) {
            $end = end($array);
            return $end ?: $default;
        }
        foreach (array_reverse($array, true) as $key => $value) {
            if ($callback($value, $key)) return $value;
        }
        return $default;
    }

    public static function pluck($array, $valueKey, $keyKey = null) {
        $result = [];
        foreach ($array as $item) {
            if (is_object($item)) $item = (array) $item;
            $value = self::get($item, $valueKey);
            if ($keyKey === null) {
                $result[] = $value;
            } else {
                $result[self::get($item, $keyKey)] = $value;
            }
        }
        return $result;
    }

    public static function only($array, $keys) {
        return array_intersect_key($array, array_fill_keys((array) $keys, null));
    }

    public static function except($array, $keys) {
        return array_diff_key($array, array_fill_keys((array) $keys, null));
    }

    public static function where($array, $key, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        return array_values(array_filter($array, function ($item) use ($key, $operator, $value) {
            if (is_object($item)) $item = (array) $item;
            $itemValue = self::get($item, $key);
            return match ($operator) {
                '=' , '==' => $itemValue == $value,
                '===' => $itemValue === $value,
                '!=' , '<>' => $itemValue != $value,
                '!==' => $itemValue !== $value,
                '>' => $itemValue > $value,
                '>=' => $itemValue >= $value,
                '<' => $itemValue < $value,
                '<=' => $itemValue <= $value,
                default => $itemValue == $value,
            };
        }));
    }

    public static function flatten($array, $depth = INF) {
        $result = [];
        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, $item);
            } else {
                $result = array_merge($result, self::flatten($item, $depth - 1));
            }
        }
        return $result;
    }

    public static function dot($array, $prepend = '') {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $result = array_merge($result, self::dot($value, $prepend . $key . '.'));
            } else {
                $result[$prepend . $key] = $value;
            }
        }
        return $result;
    }

    public static function undot($array) {
        $result = [];
        foreach ($array as $key => $value) {
            self::set($result, $key, $value);
        }
        return $result;
    }

    public static function merge($array, ...$arrays) {
        foreach ($arrays as $arr) {
            $array = array_merge($array, $arr);
        }
        return $array;
    }

    public static function crossJoin(...$arrays) {
        $results = [[]];
        foreach ($arrays as $index => $array) {
            $append = [];
            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;
                    $append[] = $product;
                }
            }
            $results = $append;
        }
        return $results;
    }

    public static function sort($array, $callback = null) {
        if ($callback === null) {
            sort($array);
            return $array;
        }
        usort($array, $callback);
        return $array;
    }

    public static function map($array, $callback) {
        $result = [];
        foreach ($array as $key => $value) {
            $result[$key] = $callback($value, $key);
        }
        return $result;
    }

    public static function filter($array, $callback = null) {
        return $callback ? array_filter($array, $callback, ARRAY_FILTER_USE_BOTH) : array_filter($array);
    }

    public static function reduce($array, $callback, $initial = null) {
        return array_reduce($array, $callback, $initial);
    }

    public static function unique($array) {
        return array_values(array_unique($array));
    }

    public static function chunk($array, $size) {
        return array_chunk($array, $size, true);
    }

    public static function pad($array, $size, $value) {
        return array_pad($array, $size, $value);
    }
}
