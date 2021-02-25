<?php

namespace Tests;

use Clickfwd\Yoyo\Twig\YoyoTwigExtension;
use Clickfwd\Yoyo\ViewProviders\TwigViewProvider;
use Twig\Extension\DebugExtension;

use function Tests\yoyo_instance;

function yoyo_twig()
{
    $yoyo = yoyo_instance();
    
    $loader = new \Twig\Loader\FilesystemLoader([
        __DIR__.'/app/resources/views/yoyo',
      ]);
      
    $twig = new \Twig\Environment($loader, [
        'cache' => __DIR__.'/compiled',
        'auto_reload' => true,
        // 'debug' => true
    ]);
    
    // Add Yoyo's Twig Extension
    
    $twig->addExtension(new YoyoTwigExtension());
    
    // Register Twig view provider for Yoyo
    
    $yoyo->registerViewProvider(function () use ($twig) {
        return new TwigViewProvider($twig);
    });
}
