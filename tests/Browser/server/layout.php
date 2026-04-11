<?php

/**
 * Minimal layout for browser test pages.
 * Includes Yoyo scripts/styles and renders a single component in isolation.
 */
function render_page(string $title, string $componentHtml): void
{
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <?php Yoyo\yoyo_styles(); ?>
    <?php Yoyo\yoyo_scripts(); ?>
</head>
<body>
    <div id="app">
        <?php echo $componentHtml; ?>
    </div>
</body>
</html>
    <?php
}
