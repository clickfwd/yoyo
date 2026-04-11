<?php

use Clickfwd\Yoyo\Yoyo;

$yoyo = new Yoyo();

$yoyo->configure([
    'namespace' => 'Tests\\App\\Yoyo\\',
]);

uses()->group('browser')->in('Browser');
