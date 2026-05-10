<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
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
        $html = self::parse($template, $data);

        return $html;
    }

    protected static function parse($template, $data) {
        preg_match_all('/\{\{route\s+\'([^\']+)\'\}\}/', $template, $routeMatches);
        foreach ($routeMatches[1] as $path) {
            $template = str_replace("{{route '$path'}}", url($path), $template);
        }
        preg_match_all('/\{\{url\s+\'([^\']+)\'\}\}/', $template, $urlMatches);
        foreach ($urlMatches[1] as $path) {
            $template = str_replace("{{url '$path'}}", url($path), $template);
        }
        $template = self::parseEach($template, $data);
        $template = self::parseIf($template, $data);
        foreach ($data as $key => $value) {
            if (is_array($value)) continue;
            $safe = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $template = str_replace('{{{' . $key . '}}}', $value, $template);
            $template = str_replace('{{' . $key . '}}', $safe, $template);
        }
        return $template;
    }

    protected static function parseEach($template, $data) {
        preg_match_all('/\{\{#each\s+(\w+)\}\}(.*?)\{\{\/each\}\}/s', $template, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $var = $match[1];
            $block = $match[2];
            $replacement = '';
            if (isset($data[$var]) && is_array($data[$var])) {
                foreach ($data[$var] as $item) {
                    $itemBlock = $block;
                    if (is_array($item)) {
                        foreach ($item as $k => $v) {
                            $itemBlock = str_replace('{{' . $k . '}}', htmlspecialchars($v, ENT_QUOTES, 'UTF-8'), $itemBlock);
                        }
                    }
                    $replacement .= $itemBlock;
                }
            }
            $template = str_replace($match[0], $replacement, $template);
        }
        return $template;
    }

    protected static function parseIf($template, $data) {
        preg_match_all('/\{\{if\s+(!?)([\w.]+)\}\}(.*?)(?:\{\{else\}\}(.*?))?\{\{endif\}\}/s', $template, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $negate = $match[1] === '!';
            $key = $match[2];
            $ifBlock = $match[3];
            $elseBlock = $match[4] ?? '';

            $value = Arr::get($data, $key);
            $truthy = $value && $value !== '' && $value !== [] && $value !== false;
            if ($negate) $truthy = !$truthy;

            $template = str_replace($match[0], $truthy ? $ifBlock : $elseBlock, $template);
        }
        return $template;
    }
}
