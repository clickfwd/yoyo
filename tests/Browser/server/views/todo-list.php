<div id="todo-list">
    <div>
        <input type="text" name="task" placeholder="What needs to be done?"
            yoyo:on="keydown[key=='Enter']" yoyo:post="add" />
    </div>

    <?php if ($this->entries): ?>
        <ul data-entries>
            <?php foreach ($this->entries as $entry): ?>
                <li data-todo-id="<?php echo $entry['id']; ?>">
                    <input type="checkbox" yoyo:get="toggle" yoyo:val.id="<?php echo $entry['id']; ?>"
                        <?php echo $entry['completed'] ? 'checked' : ''; ?> />
                    <span class="<?php echo $entry['completed'] ? 'completed' : ''; ?>">
                        <?php echo htmlspecialchars($entry['title']); ?>
                    </span>
                    <button yoyo:get="delete" yoyo:val.id="<?php echo $entry['id']; ?>" data-delete>x</button>
                </li>
            <?php endforeach; ?>
        </ul>

        <footer>
            <span data-active-count><?php echo $this->activeCount; ?> items left</span>
            <div>
                <button yoyo:get="render" yoyo:val.filter="">All</button>
                <button yoyo:get="render" yoyo:val.filter="active">Active</button>
                <button yoyo:get="render" yoyo:val.filter="completed">Completed</button>
            </div>
        </footer>
    <?php endif; ?>
</div>
