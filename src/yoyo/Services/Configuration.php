<?php

namespace Clickfwd\Yoyo\Services;

use Clickfwd\Yoyo\Concerns\Singleton;

class Configuration
{
    use Singleton;

    private static $options;

    public static $htmx = '0.2.0';

    public function __construct($options)
    {
        self::$options = array_merge([
            'namespace' => 'App\\Yoyo\\',
            'defaultSwap' => 'outerHTML',
        ], $options);
    }

    public static function get($key)
    {
        return self::$options[$key] ?? null;
    }

    public static function url()
    {
        return self::$options['url'];
    }

    public static function scriptsPath()
    {
        return rtrim(self::$options['scriptsPath'], '/');
    }

    public static function swap()
    {
        return self::$options['defaultSwap'] ?? 'outerHTML';
    }

    public static function scripts($return = false) 
    {
        return self::minify(self::javascriptAssets());
    }

    public static function styles() 
    {
        return self::minify(self::cssAssets());
    }    

    public static function javascriptAssets(): string
    {
        if (empty(self::$options['htmx'])) {
            $htmxSrc = 'https://unpkg.com/htmx.org@'.self::$htmx.'/dist/htmx.js';
        }
        else {
            $htmxSrc = self::$options['htmx'];
        }
        $scriptsPath = self::scriptsPath();
        $yoyoUrl = self::url();
        $defaultSwap = self::swap();
        
        return <<<HTML
<script src="{$htmxSrc}"></script>
<script src="{$scriptsPath}/yoyo.js"></script>
<script>
Yoyo.url = '{$yoyoUrl}';
Yoyo.config({
    defaultSwapStyle: '{$defaultSwap}',
    indicatorClass:	'yoyo-indicator',
    requestClass:	'yoyo-request',
    settlingClass:	'yoyo-settling',
    swappingClass:	'yoyo-swapping'
});
</script>
HTML;
    }

    public static function cssAssets() 
    {
        return <<<HTML
<style>
    [yoyo\:spinning], [yoyo\:spinning\.delay] {
        display: none;
    }
</style>
HTML;
    }

    protected static function minify($string)
    {
        return preg_replace('~(\v|\t|\s{2,})~m', '', $string);
    }
}
