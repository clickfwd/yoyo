<?php

include_once __DIR__.'/../vendor/autoload.php';

$classLoader = new \Composer\Autoload\ClassLoader();
$classLoader->addPsr4("Tests\\App\\", __DIR__.'/app', true);
$classLoader->register();
