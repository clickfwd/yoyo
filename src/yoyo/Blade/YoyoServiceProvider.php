<?php

namespace Clickfwd\Yoyo\Blade;

use Illuminate\Support\ServiceProvider;

class YoyoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerBladeDirectives();
    }

    protected function registerBladeDirectives()
    {
        $blade = $this->app->get('view');

        new YoyoBladeDirectives($blade);
    }
}
