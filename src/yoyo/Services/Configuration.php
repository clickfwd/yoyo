<?php

namespace Clickfwd\Yoyo\Services;

use Clickfwd\Yoyo\Concerns\Singleton;

class Configuration
{
    use Singleton;

    private static $options;

    public static $htmx = '0.3.0';

    public function __construct($options)
    {
        self::$options = array_merge([
            'namespace' => 'App\\Yoyo\\',
            'defaultSwap' => 'outerHTML',
            'historyEnabled' => false,
        ], $options);
    }

    public static function get($key, $default = null)
    {
        return self::$options[$key] ?? $default;
    }

    public static function scripts($return = false)
    {
        return self::minify(self::javascriptAssets());
    }

    public static function styles()
    {
        return self::minify(self::cssStyle());
    }

    public static function htmxSrc(): string
    {
        if (empty($htmxSrc = self::get('htmx'))) {
            $htmxSrc = 'https://unpkg.com/htmx.org@'.self::$htmx.'/dist/htmx.js';
        }

        return $htmxSrc;
    }

    public static function yoyoSrc(): string
    {
        return rtrim(self::get('scriptsPath', ''), '/').'/yoyo.js';
    }

    public static function javascriptAssets(): string
    {
        $htmxSrc = self::htmxSrc();
        $yoyoSrc = self::yoyoSrc();
        $initCode = self::javascriptInitCode();

        return <<<HTML
        <script src="{$htmxSrc}"></script>
        <script src="{$yoyoSrc}"></script>
        {$initCode}
HTML;
    }

    public static function javascriptInitCode($includeScriptTag = true): string
    {
        $yoyoRoute = self::get('url', '');
        $defaultSwap = self::get('defaultSwap', 'outerHTML');
        $historyEnabled = self::get('historyEnabled', false) ? 'true' : 'false';

        $script = <<<HTML
        Yoyo.url = '{$yoyoRoute}';
        Yoyo.config({
            defaultSwapStyle: '{$defaultSwap}',
            historyEnabled: {$historyEnabled},
            indicatorClass:	'yoyo-indicator',
            requestClass:	'yoyo-request',
            settlingClass:	'yoyo-settling',
            swappingClass:	'yoyo-swapping'
        });
HTML;

        if ($includeScriptTag) {
            $script = "<script>{$script}</script>";
        }

        return $script;
    }

    public static function cssStyle($includeStyleTag = true)
    {
        $style = <<<HTML
        [yoyo\:spinning], [yoyo\:spinning\.delay] {
            display: none;
        }
HTML;

        if ($includeStyleTag) {
            $style = "<style>$style</style>";
        }

        return $style;
    }

    protected static function minify($string)
    {
        return preg_replace('~(\v|\t|\s{2,})~m', '', $string);
    }
}
