<?php

namespace Tests\App\Resolvers;

use Clickfwd\Yoyo\ComponentResolver;
use Clickfwd\Yoyo\View;
use Clickfwd\Yoyo\ViewProviders\YoyoViewProvider;

class CustomComponentResolver extends ComponentResolver
{
    protected $name = 'custom';

    public function getViewProvider()
    {
        return new YoyoViewProvider(new View(__DIR__.'/../resources/views/yoyo'));
    }
}
