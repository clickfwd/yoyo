<div yoyo:swap="morphdom">
    <button id="spinner-swap-submit" yoyo:get="save" yoyo:spinning.attr="disabled" <?php echo $saved ? 'disabled' : ''; ?>>Save</button>
    <span id="spinner-swap-class" yoyo:spinning.class="highlighted" class="<?php echo $saved ? 'highlighted' : ''; ?>">Highlighted</span>
    <span id="spinner-swap-display" yoyo:spinning style="display: <?php echo $saved ? 'block' : 'none'; ?>">Visible</span>
    <span id="spinner-swap-state"><?php echo $saved ? 'Saved' : 'Unsaved'; ?></span>
    <button id="spinner-swap-target" yoyo:get="save" yoyo:swap="innerHTML"
        yoyo:target="#spinner-swap-external" yoyo:select="#spinner-swap-state">Save to external target</button>
</div>
