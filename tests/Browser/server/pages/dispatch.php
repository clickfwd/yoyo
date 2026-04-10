<?php

require __DIR__.'/../layout.php';

ob_start();
?>
<div id="dispatch-test">
    <div data-listener-area>
        <?php echo Yoyo\yoyo_render('dispatch-listener', [], ['id' => 'dispatch-listener']); ?>
    </div>
    <div data-bystander-area>
        <?php echo Yoyo\yoyo_render('dispatch-bystander', [
            'label' => 'unchanged',
        ], ['id' => 'dispatch-bystander']); ?>
    </div>
    <div data-buttons>
        <button id="btn-dispatch" onclick="Yoyo.dispatch('post-created', { postId: 42 })">Dispatch</button>
        <button id="btn-dispatch-to" onclick="Yoyo.dispatchTo('dispatch-listener', 'post-created', { postId: 7 })">DispatchTo</button>
        <button id="btn-dispatch-multi" onclick="Yoyo.dispatch('status-changed', { status: 'active', reason: 'manual' })">Multi Params</button>
        <button id="btn-dispatch-no-params" onclick="Yoyo.dispatch('simple-refresh')">No Params</button>
        <button id="btn-dispatch-nonexistent" onclick="Yoyo.dispatchTo('nonexistent', 'post-created', { postId: 1 })">Nonexistent</button>
    </div>
</div>
<?php

render_page('Dispatch', ob_get_clean());
