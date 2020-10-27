<?php

namespace Clickfwd\Yoyo\Blade;

class YoyoBladeDirectives
{
    public function __construct($blade)
    {
        $blade->directive('yoyo', [$this, 'yoyo']);

        $blade->directive('yoyo_scripts', [$this, 'yoyo_scripts']);

        $blade->directive('spinning', [$this, 'spinning']);

        $blade->directive('endspinning', [$this, 'endspinning']);

        $blade->directive('emit', [$this, 'emit']);

        $blade->directive('emitTo', [$this, 'emitTo']);
    }

    public function yoyo($expression)
    {
        return <<<yoyo
<?php
\$yoyo = new \Clickfwd\Yoyo\Yoyo();
if (Yoyo\is_spinning()) {
    echo \$yoyo->mount({$expression})->refresh();
} else {
    echo \$yoyo->mount({$expression})->render();
}
?>
yoyo;
    }

    public function yoyo_scripts()
    {
        return '<?php Yoyo\yoyo_scripts(); ?>';
    }

    public function spinning($expression)
    {
        return $expression !== ''
        ? "<?php if(\$spinning && {$expression}): ?>"
        : '<?php if($spinning): ?>';
    }

    public function endspinning()
    {
        return '<?php endif; ?>';
    }

    public function emit($expression)
    {
        return "<?php \$this->emit({$expression}); ?>";
    }

    public function emitTo($expression)
    {
        return "<?php \$this->emitTo({$expression}); ?>";
    }

    public function emitSelf($expression)
    {
        return "<?php \$this->emitSelf({$expression}); ?>";
    }

    public function emitUp($expression)
    {
        return "<?php \$this->emitUp({$expression}); ?>";
    }
}
