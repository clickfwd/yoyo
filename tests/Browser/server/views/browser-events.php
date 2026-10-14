<div id="browser-events">
    <span id="browser-event-message"><?php echo $message; ?></span>
    <button id="save-event" yoyo:post="save">Save</button>
    <button id="save-event-no-swap" yoyo:post="saveWithoutSwap">Save without swap</button>
    <button id="save-event-no-render" yoyo:post="saveWithoutRender">Save without render</button>
    <button id="save-event-delayed" yoyo:post="save" yoyo:swap="outerHTML swap:0.1s">Save with delay</button>
    <button id="event-page-two" yoyo:get="goToPage(2)">Page 2</button>
</div>
