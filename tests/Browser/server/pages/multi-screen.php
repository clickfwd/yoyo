<?php

require __DIR__.'/../layout.php';

ob_start();
?>
<div id="multi-screen-test">
    <?php echo Yoyo\yoyo_render('multi-screen', [], ['id' => 'wizard']); ?>
</div>
<?php

render_page('Multi Screen', ob_get_clean());
