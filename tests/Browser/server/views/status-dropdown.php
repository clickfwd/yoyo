<div data-component="status" data-item="<?php echo $itemId; ?>">
    <button
        data-action="toggle-menu"
        data-status="<?php echo $status; ?>"
        yoyo:get="toggleMenu"
    ><?php echo ucfirst($status); ?> ▾</button>

    <?php if ($isOpen): ?>
    <ul data-menu>
        <?php foreach (['draft', 'active', 'archived'] as $option): ?>
        <li>
            <button
                data-option="<?php echo $option; ?>"
                yoyo:get="setStatus"
                yoyo:val.new-status="<?php echo $option; ?>"
                <?php echo $option === $status ? 'data-selected' : ''; ?>
            ><?php echo ucfirst($option); ?></button>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
