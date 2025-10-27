<?php

namespace Tests\App\Resolvers;

use Clickfwd\Yoyo\ComponentResolver;
use Clickfwd\Yoyo\ViewProviders\BladeViewProvider;

use function Tests\blade;

class BladeComponentResolver extends ComponentResolver
{
    protected $name = 'blade';

    public function getViewProvider()
    {
        return new BladeViewProvider(blade());
    }
}
