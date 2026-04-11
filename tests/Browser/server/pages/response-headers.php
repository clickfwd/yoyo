<?php

require __DIR__.'/../layout.php';

ob_start();
?>
<div id="response-headers-test">
    <div data-component-area>
        <?php echo Yoyo\yoyo_render('response-headers', [], ['id' => 'response-headers']); ?>
    </div>
    <div id="retarget-receiver">
        <span id="retarget-content">original</span>
    </div>
    <div id="event-log"></div>
    <script>
        // Listen for HX-Trigger events and log them to #event-log
        document.body.addEventListener('custom-event', function() {
            document.getElementById('event-log').textContent += 'custom-event,';
        });
        document.body.addEventListener('settle-event', function() {
            document.getElementById('event-log').textContent += 'settle-event,';
        });
    </script>
</div>
<?php

render_page('Response Headers', ob_get_clean());
