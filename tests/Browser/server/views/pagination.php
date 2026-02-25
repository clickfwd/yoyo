<div id="pagination">
    <?php if ($this->results): ?>
        <ul data-results>
            <?php foreach ($this->results as $item): ?>
                <li><?php echo htmlspecialchars($item['title']); ?></li>
            <?php endforeach; ?>
        </ul>

        <div data-page-info>
            Showing <?php echo $this->start; ?> to <?php echo $this->end; ?>
            of <?php echo 12; ?> results
        </div>

        <nav data-nav>
            <?php for ($i = 1; $i <= $this->totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>"
                    yoyo:get="render"
                    yoyo:val.page="<?php echo $i; ?>"
                    data-page="<?php echo $i; ?>"
                    class="<?php echo $page == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</div>
