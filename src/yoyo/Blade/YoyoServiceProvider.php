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
        $blade = $this->app->get('view');

        new YoyoBladeDirectives($blade);
    }
}
