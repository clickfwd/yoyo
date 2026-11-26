<?php

use Clickfwd\Yoyo\View;

require __DIR__.'/../../vendor/autoload.php';

$mode = $argv[1];
$failure = new $argv[2]('template failed');
$view = new View(__DIR__.'/../app/resources/views/yoyo');
$depth = ob_get_level();
ob_start();
echo 'caller';
$caller = ob_get_status();

try {
    $view->render('render-ownership/buffer-failure', ['mode' => $mode, 'failure' => $failure]);
    exit(1);
} catch (Throwable $caught) {
    $buffers = ob_get_status(true);
    fwrite(STDERR, json_encode([
        'same_exception' => $caught === $failure,
        'caller_buffer_preserved' => ($buffers[$depth] ?? null) === $caller,
        'remaining_buffers' => ob_get_level() - $depth,
    ], JSON_THROW_ON_ERROR));
}
