<div id="counter" yoyo:val.count="<?php echo $count; ?>">
    <button yoyo:get="increment">+</button>
    <button yoyo:get="decrement">-</button>
    <span><?php echo $count; ?></span>
    <span><?php echo $this->currentCount; ?></span>
</div>