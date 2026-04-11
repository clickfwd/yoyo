<?php

require __DIR__.'/../layout.php';

ob_start();
?>
<div id="skip-render-test">
    <div data-items>
        <?php echo Yoyo\yoyo_render('delete-item', [
            'itemId' => 1,
            'title' => 'First Item',
        ], ['id' => 'item-1']); ?>
        <?php echo Yoyo\yoyo_render('delete-item', [
            'itemId' => 2,
            'title' => 'Second Item',
        ], ['id' => 'item-2']); ?>
    </div>
</div>
<?php

render_page('Skip Render', ob_get_clean());
