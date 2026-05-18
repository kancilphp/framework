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
    protected static $tenantColumn = null;
    protected static $dateFormat = 'Y-m-d H:i:s';

    protected static function missingColumn($col) {
        $hint = '';
        if (in_array($col, ['created_at', 'updated_at'])) {
            $hint = 'Set $timestamps = false in ' . static::class . ' or add `' . $col . '` column.';
        } elseif ($col === 'deleted_at') {
            $hint = 'Set $softDelete = false in ' . static::class . ' or add `' . $col . '` column.';
        }
        $msg = 'Table `' . static::$table . '` missing column `' . $col . '`. ' . $hint;
        Response::error(500, $msg);
    }

    protected static function dbCatch($fn) {
        try { return $fn(); }
        catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'Unknown column') !== false) {
                preg_match("/Unknown column '([^']+)'/", $e->getMessage(), $m);
                static::missingColumn($m[1] ?? '?');
            }
            throw $e;
        }
    }

    public static function query($sql = null, $params = []) {
        $q = Query::table(static::$table);
        if (static::$softDelete) {
            $q->whereNull('deleted_at');
        }
        $col = static::$tenantColumn;
        if ($col && \Core\Request::$tenant) {
            $q->where($col, (int) \Core\Request::$tenant);
        }
        return $q;
    }

    public static function table() {
        return static::$table;
    }

    public static function find($id) {
        return static::dbCatch(fn() =>
            static::query()->where(static::$primaryKey, $id)->first()
        );
    }

    public static function all() {
        return static::dbCatch(fn() =>
            static::query()->get()
        );
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
        return static::dbCatch(fn() =>
            Query::table(static::$table)->insert($data)
        );
    }

    public static function updateById($id, array $data) {
        if (static::$timestamps) {
            $data['updated_at'] = date(static::$dateFormat);
        }
        return static::dbCatch(fn() =>
            static::query()->where(static::$primaryKey, $id)->update($data)
        );
    }

    public static function deleteById($id) {
        if (static::$softDelete) {
            return static::dbCatch(fn() =>
                static::query()->where(static::$primaryKey, $id)->update([
                    'deleted_at' => date(static::$dateFormat),
                ])
            );
        }
        return static::dbCatch(fn() =>
            static::query()->where(static::$primaryKey, $id)->delete()
        );
    }

    public static function destroy($ids) {
        $ids = is_array($ids) ? $ids : [$ids];
        if (empty($ids)) return false;
        if (static::$softDelete) {
            return static::dbCatch(fn() =>
                static::query()->whereIn(static::$primaryKey, $ids)->update([
                    'deleted_at' => date(static::$dateFormat),
                ])
            );
        }
        return static::dbCatch(fn() =>
            static::query()->whereIn(static::$primaryKey, $ids)->delete()
        );
    }

    public static function withTrashed() {
        return Query::table(static::$table);
    }

    public static function trashed() {
        return Query::table(static::$table)->whereNotNull('deleted_at');
    }

    public static function restore($id) {
        return static::dbCatch(fn() =>
            Query::table(static::$table)
                ->where(static::$primaryKey, $id)
                ->update(['deleted_at' => null])
        );
    }

    public static function forceDeleteById($id) {
        return static::dbCatch(fn() =>
            Query::table(static::$table)
                ->where(static::$primaryKey, $id)
                ->delete()
        );
    }

    public static function count() {
        return static::dbCatch(fn() =>
            static::query()->count()
        );
    }
}
