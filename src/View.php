<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;

use DevTheorem\Handlebars\Handlebars;

class View {
    public static function render($module, $view, $data = []) {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $module)) {
            Response::error(500, 'Invalid module name');
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $view)) {
            Response::error(500, 'Invalid view name');
        }

        $theme = Config::get('APP_THEME', 'default');
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $theme)) {
            $theme = 'default';
        }
        $file = BASE_PATH . '/app/Themes/' . $theme . '/' . $module . '/' . $view . '.hbs';
        if (!file_exists($file)) {
            $file = BASE_PATH . '/app/themes/default/' . $module . '/' . $view . '.hbs';
        }
        if (!file_exists($file)) {
            Response::error(500, 'View not found: ' . $module . '/' . $view);
        }
        $template = file_get_contents($file);

        $repl = fn($m) => url($m[1]);
        $template = preg_replace_callback('/\{\{route\s+\'([^\']+)\'\}\}/', $repl, $template);
        $template = preg_replace_callback('/\{\{url\s+\'([^\']+)\'\}\}/', $repl, $template);

        $options = [];
        if (strpos($template, '{{>') !== false) {
            $partials = self::getPartials();
            if ($partials) $options['partials'] = $partials;
        }

        try {
            $renderer = Handlebars::compile($template);
            return $renderer($data, $options);
        } catch (\Throwable $e) {
            Response::error(500, 'Template error: ' . $e->getMessage());
        }
    }

    protected static function getPartials() {
        static $dir = null, $partials = null;
        if ($partials !== null) return $partials;

        $theme = Config::get('APP_THEME', 'default');
        $dir = BASE_PATH . '/app/Themes/' . $theme . '/partials/';

        if (!is_dir($dir)) return $partials = [];

        $partials = [];
        foreach (glob($dir . '*.hbs') as $pf) {
            $name = basename($pf, '.hbs');
            $content = file_get_contents($pf);
            $content = preg_replace_callback('/\{\{route\s+\'([^\']+)\'\}\}/', fn($m) => url($m[1]), $content);
            $content = preg_replace_callback('/\{\{url\s+\'([^\']+)\'\}\}/', fn($m) => url($m[1]), $content);
            $partials[$name] = Handlebars::compile($content);
        }
        return $partials;
    }
}
