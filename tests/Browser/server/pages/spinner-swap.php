<?php

require __DIR__.'/../layout.php';

$html = '<script src="/assets/js/morphdom.js"></script>';
$html .= Yoyo\yoyo_render('spinner-swap', [], ['id' => 'spinner-swap-form']);
$html .= '<div id="spinner-swap-external">Waiting</div>';

render_page('Spinner response state', $html);
