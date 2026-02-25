<?php

require __DIR__.'/../layout.php';

ob_start();
?>
<div id="events-test">
    <div data-badge-area>
        <?php echo Yoyo\yoyo_render('notification-badge', [
            'count' => 0,
        ], ['id' => 'badge']); ?>
    </div>
    <div data-buttons>
        <?php echo Yoyo\yoyo_render('action-button', [
            'label' => 'Send Email',
        ], ['id' => 'btn-email']); ?>
        <?php echo Yoyo\yoyo_render('action-button', [
            'label' => 'Add Item',
        ], ['id' => 'btn-add']); ?>
    </div>
</div>
<?php

render_page('Events', ob_get_clean());
