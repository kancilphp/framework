<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Model extends DB {
    protected static $table;
    protected static $primaryKey = 'id';

    public static function table() {
        return static::$table;
    }

    public static function find($id) {
        return Query::table(static::$table)->where(static::$primaryKey, $id)->first();
    }

    public static function all() {
        return Query::table(static::$table)->get();
    }

    public static function where($column, $value) {
        return Query::table(static::$table)->where($column, $value);
    }

    public static function create(array $data) {
        return Query::table(static::$table)->insert($data);
    }

    public static function updateById($id, array $data) {
        return Query::table(static::$table)->where(static::$primaryKey, $id)->update($data);
    }

    public static function deleteById($id) {
        return Query::table(static::$table)->where(static::$primaryKey, $id)->delete();
    }

    public static function count() {
        return Query::table(static::$table)->count();
    }
}
