<?php

use Clickfwd\Yoyo\ContainerResolver;
use Clickfwd\Yoyo\Containers\YoyoContainer;
use Clickfwd\Yoyo\Yoyo;

ContainerResolver::setPreferred(YoyoContainer::getInstance());

$yoyo = new Yoyo(YoyoContainer::getInstance());
$yoyo->configure([
    'namespace' => 'Tests\\App\\Yoyo\\',
]);
