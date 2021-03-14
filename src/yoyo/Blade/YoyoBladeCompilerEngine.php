<?php

namespace Clickfwd\Yoyo\Blade;

use Illuminate\View\Engines\CompilerEngine as LaravelCompilerEngine;

class YoyoBladeCompilerEngine extends LaravelCompilerEngine
{
    protected $yoyoComponent;

    protected $isRenderingYoyoComponent;

    public function startYoyoRendering($component)
    {
        $this->yoyoComponent = $component;

        $this->isRenderingYoyoComponent = true;
    }

    public function stopYoyoRendering()
    {
        $this->isRenderingYoyoComponent = false;
    }

    /**
     * /vendor/illuminate/view/Engines/PhpEngine.php.
     */
    protected function evaluatePath($__path, $__data)
    {
        if (! $this->isRenderingYoyoComponent) {
            return parent::evaluatePath($__path, $__data);
        }

        $obLevel = ob_get_level();

        ob_start();

        try {
            \Closure::bind(function () use ($__path, $__data) {
                extract($__data, EXTR_SKIP);
                include $__path;
            }, $this->yoyoComponent ? $this->yoyoComponent : $this)();
        } catch (\Throwable $e) {
            $this->handleViewException($e, $obLevel);
        }

        return ltrim(ob_get_clean());
    }
}
