<?php

require __DIR__.'/../layout.php';

ob_start();
?>
<div id="modal-test">
    <?php echo Yoyo\yoyo_render('modal-trigger', [], ['id' => 'modal-trigger']); ?>
</div>
<?php

render_page('Modal', ob_get_clean());
