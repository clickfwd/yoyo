<div data-component="favorite" data-item="<?php echo $itemId; ?>">
    <button
        data-action="toggle"
        data-favorited="<?php echo $isFavorited; ?>"
        yoyo:get="toggle"
    ><?php echo $isFavorited ? '★' : '☆'; ?></button>
</div>
