<div id="live-search" yoyo:trigger="input delay:300ms from:input[name='q']">
    <input type="text" name="q" value="<?php echo htmlspecialchars($q ?? ''); ?>"
        placeholder="Search..." />

    <?php if ($this->results): ?>
        <ul data-results>
            <?php foreach ($this->results as $row): ?>
                <li><?php echo htmlspecialchars($row['title']); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php elseif ($q): ?>
        <p data-no-results>No results found</p>
    <?php endif; ?>
</div>
