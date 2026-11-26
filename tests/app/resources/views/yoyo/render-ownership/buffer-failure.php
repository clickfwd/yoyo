<?php

echo 'partial';

if ($mode === 'nonremovable') {
    ob_start(null, 0, 0);
} else {
    ob_start(function () {
        throw new LogicException('handler failed');
    });
}

echo 'inner';
throw $failure;
