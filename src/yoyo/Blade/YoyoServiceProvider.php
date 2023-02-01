<?php

namespace Clickfwd\Yoyo\Blade;

use Illuminate\Support\ServiceProvider;

class YoyoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerBladeDirectives();
        $this->registerViewCompilerEngine();
    }

    protected function registerViewCompilerEngine()
    {
        $this->app->make('view.engine.resolver')->register('blade', function () {
            return new YoyoBladeCompilerEngine($this->app['blade.compiler']);
        });
    }

    protected function registerBladeDirectives()
    {
        if (method_exists($this->app->get('view'), 'directive')) {
            $blade = $this->app->get('view');
        } else {
            $blade = $this->app->get('view')->getEngineResolver()->resolve('blade')->getCompiler();
        }

        new YoyoBladeDirectives($blade);
    }
}
