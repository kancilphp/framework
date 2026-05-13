<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core\Drivers;
use Core\Config;
use Core\Response;
use PDOException;
use PDO;
class MySQL {
    protected static $pdo = null;

    protected static function connect() {
        if (self::$pdo !== null) return self::$pdo;
        $host = Config::get('DB_HOST', '127.0.0.1');
        $dbname = Config::get('DB_NAME', 'kancil');
        $user = Config::get('DB_USER', 'root');
        $pass = Config::get('DB_PASS', '');
        try {
            self::$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            if (Config::get('APP_DEBUG', false)) {
                Response::error(500, 'DB Connection Error: ' . $e->getMessage());
            }
            self::$pdo = null;
            return null;
        }
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
