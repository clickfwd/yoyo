<?php

namespace Tests\App\Resolvers;

use Clickfwd\Yoyo\ComponentResolver;
use Clickfwd\Yoyo\ViewProviders\TwigViewProvider;

use function Tests\twig;

class TwigComponentResolver extends ComponentResolver
{
    protected $name = 'twig';

    public function getViewProvider()
    {
        return new TwigViewProvider(twig());
    }
}
