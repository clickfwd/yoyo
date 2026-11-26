<?php

require __DIR__.'/../layout.php';

$parent = Yoyo\yoyo_render('spinner-lifecycle', [], ['id' => 'parent']);
$child = Yoyo\yoyo_render('spinner-lifecycle', ['scope' => 'child'], ['id' => 'child']);

render_page('Spinner lifecycle', $parent.$child);
