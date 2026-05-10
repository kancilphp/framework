<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Event {
    protected static $listeners = [];

    public static function listen($event, $callback) {
        if (!isset(self::$listeners[$event])) {
            self::$listeners[$event] = [];
        }
        self::$listeners[$event][] = $callback;
    }

    public static function fire($event, $data = []) {
        if (!isset(self::$listeners[$event])) return;
        foreach (self::$listeners[$event] as $callback) {
            call_user_func($callback, $data);
        }
    }

    public static function dispatch($event, $data = []) {
        return self::fire($event, $data);
    }
}
