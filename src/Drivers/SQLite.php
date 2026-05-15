<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core\Drivers;
use Core\Config;
use PDO;
class SQLite {
    protected static $pdo = null;

    protected static function connect() {
        if (self::$pdo !== null) return self::$pdo;
        $path = Config::get('DB_NAME', BASE_PATH . '/database/kancil.sqlite');
        self::$pdo = new PDO("sqlite:$path");
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->exec("PRAGMA journal_mode=WAL");
        self::$pdo->exec("PRAGMA foreign_keys=ON");
        return self::$pdo;
    }

    public static function query($sql, $params = []) {
        $pdo = self::connect();
        if (!$pdo) return [];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function first($sql, $params = []) {
        $pdo = self::connect();
        if (!$pdo) return null;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function execute($sql, $params = []) {
        $pdo = self::connect();
        if (!$pdo) return false;
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function begin() {
        $pdo = self::connect();
        if ($pdo) $pdo->beginTransaction();
    }

    public static function commit() {
        $pdo = self::connect();
        if ($pdo) $pdo->commit();
    }

    public static function rollback() {
        $pdo = self::connect();
        if ($pdo) $pdo->rollBack();
    }

    public static function transaction($callback) {
        self::begin();
        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (\Exception $e) {
            self::rollback();
            throw $e;
        }
    }

    public static function lastInsertId() {
        $pdo = self::connect();
        return $pdo ? (int) $pdo->lastInsertId() : 0;
    }

    public static function raw($sql) {
        return $sql;
    }
}
