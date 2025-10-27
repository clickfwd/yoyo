<?php

use Clickfwd\Yoyo\Yoyo;
use Clickfwd\Yoyo\ContainerResolver;
use Clickfwd\Yoyo\Containers\YoyoContainer;

ContainerResolver::setPreferred(YoyoContainer::getInstance());

$yoyo = new Yoyo(YoyoContainer::getInstance());
$yoyo->configure([
    'namespace' => 'Tests\\App\\Yoyo\\',
]);
