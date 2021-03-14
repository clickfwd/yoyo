<?php

namespace Tests;

use Clickfwd\Yoyo\Twig\YoyoTwigExtension;
use Clickfwd\Yoyo\ViewProviders\TwigViewProvider;

function yoyo_twig()
{
    $yoyo = yoyo_instance();
    
    $yoyo->registerViewProvider(function () {
        return new TwigViewProvider(twig());
    });
}

function twig()
{
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
    
    return $twig;
}
