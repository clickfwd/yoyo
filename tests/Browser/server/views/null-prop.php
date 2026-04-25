<div id="null-prop">
    <span data-icon-slot-type="<?php echo gettype($iconSlot); ?>"></span>
    <span data-icon-slot-display><?php echo $iconSlot === null ? 'NULL_OK' : 'GOT:'.var_export($iconSlot, true); ?></span>
    <span data-enabled-type="<?php echo gettype($enabled); ?>"></span>
    <span data-enabled-display><?php echo $enabled === false ? 'FALSE_OK' : 'GOT:'.var_export($enabled, true); ?></span>
    <span data-clicks><?php echo $clicks; ?></span>
    <button
        data-action="increment"
        yoyo:get="increment"
        yoyo:val.icon-slot="null"
        yoyo:val.enabled="false"
    >+</button>
</div>
