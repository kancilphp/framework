<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Model extends DB {
    protected static $table;
    protected static $primaryKey = 'id';
    protected static $timestamps = true;
    protected static $softDelete = true;
    protected static $dateFormat = 'Y-m-d H:i:s';

    protected static function query() {
        $q = Query::table(static::$table);
        if (static::$softDelete) {
            $q->whereNull('deleted_at');
        }
        return $q;
    }

    public static function table() {
        return static::$table;
    }

    public static function find($id) {
        return static::query()->where(static::$primaryKey, $id)->first();
    }

    public static function all() {
        return static::query()->get();
    }

    public static function where($column, $value) {
        return static::query()->where($column, $value);
    }

    public static function whereIn($column, $values) {
        return static::query()->whereIn($column, $values);
    }

    public static function whereLike($column, $value) {
        return static::query()->whereLike($column, $value);
    }

    public static function orderBy($column, $direction = 'ASC') {
        return static::query()->orderBy($column, $direction);
    }

    public static function limit($limit) {
        return static::query()->limit($limit);
    }

    public static function create(array $data) {
        if (static::$timestamps) {
            $now = date(static::$dateFormat);
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
        }
        return Query::table(static::$table)->insert($data);
    }

    public static function updateById($id, array $data) {
        if (static::$timestamps) {
            $data['updated_at'] = date(static::$dateFormat);
        }
        return static::query()->where(static::$primaryKey, $id)->update($data);
    }

    public static function deleteById($id) {
        if (static::$softDelete) {
            return static::query()->where(static::$primaryKey, $id)->update([
                'deleted_at' => date(static::$dateFormat),
            ]);
        }
        return static::query()->where(static::$primaryKey, $id)->delete();
    }

    public static function destroy($ids) {
        $ids = is_array($ids) ? $ids : [$ids];
        if (empty($ids)) return false;
        if (static::$softDelete) {
            return static::query()->whereIn(static::$primaryKey, $ids)->update([
                'deleted_at' => date(static::$dateFormat),
            ]);
        }
        return static::query()->whereIn(static::$primaryKey, $ids)->delete();
    }

    public static function withTrashed() {
        return Query::table(static::$table);
    }

    public static function trashed() {
        return Query::table(static::$table)->whereNotNull('deleted_at');
    }

    public static function restore($id) {
        return Query::table(static::$table)
            ->where(static::$primaryKey, $id)
            ->update(['deleted_at' => null]);
    }

    public static function forceDeleteById($id) {
        return Query::table(static::$table)
            ->where(static::$primaryKey, $id)
            ->delete();
    }

    public static function count() {
        return static::query()->count();
    }
}
