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

    protected static function parse($template, $data, $parentData = null, $rootData = null) {
        if ($rootData === null) $rootData = $data;
        if ($parentData === null) $parentData = $data;

        preg_match_all('/\{\{route\s+\'([^\']+)\'\}\}/', $template, $m);
        foreach ($m[1] as $path) {
            $template = str_replace("{{route '$path'}}", url($path), $template);
        }
        preg_match_all('/\{\{url\s+\'([^\']+)\'\}\}/', $template, $m);
        foreach ($m[1] as $path) {
            $template = str_replace("{{url '$path'}}", url($path), $template);
        }

        $template = self::parsePartials($template, $data, $parentData, $rootData);
        $template = self::parseWith($template, $data, $parentData, $rootData);
        $template = self::parseEach($template, $data, $parentData, $rootData);
        $template = self::parseIf($template, $data, $parentData, $rootData);

        foreach ($rootData as $k => $v) {
            if (!is_array($v)) {
                $template = str_replace('{{@root.' . $k . '}}', htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'), $template);
            }
        }

        foreach ($parentData as $k => $v) {
            if (!is_array($v)) {
                $template = str_replace('{{@parent.' . $k . '}}', htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'), $template);
            }
        }

        foreach ($data as $key => $value) {
            if (strpos($key, '@') === 0) continue;
            if (is_array($value)) continue;
            $safe = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
            $template = str_replace('{{{' . $key . '}}}', (string)$value, $template);
            $template = str_replace('{{' . $key . '}}', $safe, $template);
        }

        return $template;
    }

    protected static function findTopLevelEachBlocks($template) {
        $blocks = [];
        $stack = [];
        $len = strlen($template);
        $i = 0;

        while ($i < $len) {
            if (substr($template, $i, 8) === '{{#each ') {
                $varEnd = strpos($template, '}}', $i + 8);
                if ($varEnd === false) break;
                $var = trim(substr($template, $i + 8, $varEnd - $i - 8));
                if (preg_match('/^\w+$/', $var)) {
                    array_push($stack, ['var' => $var, 'start' => $i, 'contentStart' => $varEnd + 2, 'top' => empty($stack)]);
                    $i = $varEnd + 2;
                    continue;
                }
            }

            if (substr($template, $i, 9) === '{{/each}}') {
                if (!empty($stack)) {
                    $item = array_pop($stack);
                    $item['end'] = $i + 9;
                    $item['full'] = substr($template, $item['start'], $item['end'] - $item['start']);
                    $item['block'] = substr($template, $item['contentStart'], $i - $item['contentStart']);
                    if ($item['top']) $blocks[] = $item;
                }
                $i += 9;
                continue;
            }

            $i++;
        }

        usort($blocks, fn($a, $b) => $b['start'] - $a['start']);
        return $blocks;
    }

    protected static function parseEach($template, $data, $parentData = [], $rootData = null) {
        if ($rootData === null) $rootData = $data;

        $blocks = self::findTopLevelEachBlocks($template);

        foreach ($blocks as $block) {
            $replacement = '';
            if (isset($data[$block['var']]) && is_array($data[$block['var']])) {
                $idx = 0;
                foreach ($data[$block['var']] as $k => $item) {
                    $itemBlock = $block['block'];
                    $itemData = is_array($item) ? $item : [];

                    $mergedParent = array_merge($itemData, $data);
                    $itemBlock = self::parseEach($itemBlock, $itemData, $mergedParent, $rootData);
                    $itemBlock = self::parseIf($itemBlock, $itemData, $data, $rootData);
                    $itemBlock = self::injectScope($itemBlock, $itemData, $parentData, $rootData, $idx);

                    if (!is_int($k)) {
                        $itemBlock = str_replace('{{@key}}', htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8'), $itemBlock);
                    }

                    $replacement .= $itemBlock;
                    $idx++;
                }
            }

            $template = substr_replace($template, $replacement, $block['start'], strlen($block['full']));
        }

        return self::injectScope($template, $data, $parentData, $rootData, null);
    }

    protected static function injectScope($template, $data, $parentData, $rootData, $idx) {
        foreach ($data as $k => $v) {
            if (is_array($v)) continue;
            if (strpos($k, '@') === 0) continue;
            $template = str_replace('{{' . $k . '}}', htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'), $template);
        }

        foreach ($parentData as $pk => $pv) {
            if (!is_array($pv)) {
                $template = str_replace('{{@parent.' . $pk . '}}', htmlspecialchars((string)$pv, ENT_QUOTES, 'UTF-8'), $template);
            }
        }

        foreach ($rootData as $rk => $rv) {
            if (!is_array($rv)) {
                $template = str_replace('{{@root.' . $rk . '}}', htmlspecialchars((string)$rv, ENT_QUOTES, 'UTF-8'), $template);
            }
        }

        if ($idx !== null) {
            $template = str_replace('{{@index}}', $idx, $template);
        }

        return $template;
    }

    protected static function parsePartials($template, $data, $parentData = [], $rootData = null) {
        return preg_replace_callback('/\{\{\>\s*([a-zA-Z0-9_\/-]+)\}\}/', function($match) use ($data, $parentData, $rootData) {
            static $dir = null;
            if ($dir === null) {
                $theme = Config::get('APP_THEME', 'default');
                $dir = BASE_PATH . '/app/Themes/' . $theme . '/partials/';
            }
            $file = $dir . $match[1] . '.hbs';
            if (!file_exists($file)) return '';
            return self::parse(file_get_contents($file), $data, $parentData, $rootData);
        }, $template);
    }

    protected static function parseWith($template, $data, $parentData = [], $rootData = null) {
        return preg_replace_callback('/\{\{#with\s+([\w.]+)\}\}(.*?)\{\{\/with\}\}/s', function($match) use ($data, $parentData, $rootData) {
            $scope = Arr::get($data, $match[1]);
            if (!is_array($scope)) return '';
            return self::parse($match[2], array_merge($data, $scope), $parentData, $rootData);
        }, $template);
    }

    protected static function parseIf($template, $data, $parentData = [], $rootData = null) {
        preg_match_all('/\{\{if\s+(!?)([\w.]+)\}\}(.*?)(?:\{\{else\}\}(.*?))?\{\{endif\}\}/s', $template, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $negate = $match[1] === '!';
            $key = $match[2];
            $ifBlock = $match[3];
            $elseBlock = $match[4] ?? '';

            $value = Arr::get($data, $key);
            $truthy = $value && $value !== '' && $value !== [] && $value !== false;
            if ($negate) $truthy = !$truthy;

            $block = $truthy ? $ifBlock : $elseBlock;
            $template = str_replace($match[0], self::parse($block, $data, $parentData, $rootData), $template);
        }
        return $template;
    }
}
