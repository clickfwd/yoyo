<?php

namespace Clickfwd\Yoyo\Services;

use Clickfwd\Yoyo\Concerns\Singleton;

class Configuration
{
    use Singleton;

    private static $options;

    public static $htmx = '0.1.2';

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
        return self::$options['scriptsPath'];
    }

    public static function swap()
    {
        return self::$options['defaultSwap'] ?? 'outerHTML';
    }

    public static function scripts(): void
    {
        ?>
        <script src="https://unpkg.com/htmx.org@<?php echo self::$htmx; ?>/dist/htmx.js"></script>
        <script src="<?php echo self::scriptsPath(); ?>/yoyo.js"></script>
        <script>
        Yoyo.config.defaultSwapStyle = "<?php echo self::swap(); ?>";
        YoyoFactory.url = '<?php echo self::url(); ?>';
        </script>
        <?php
    }
}
