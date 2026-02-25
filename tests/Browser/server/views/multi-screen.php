<div data-component="multi-screen">
<?php if ($this->actionMatches(['render', 'closeModal'])): ?>
    <div data-screen="initial">
        <p data-info>Ready to begin</p>
        <button data-action="open" yoyo:get="open">Open Form</button>
    </div>
<?php elseif ($this->actionMatches('open')): ?>
    <div data-screen="form">
        <input type="text" name="message" value="<?php echo htmlspecialchars($message); ?>" placeholder="Enter message" />
        <button data-action="submit" yoyo:post="submit">Submit</button>
        <button data-action="cancel" yoyo:get="render">Cancel</button>
    </div>
<?php elseif ($this->actionMatches('submit')): ?>
    <div data-screen="success">
        <p data-result>Submitted: <?php echo htmlspecialchars($message); ?></p>
        <button data-action="reset" yoyo:get="render">Start Over</button>
    </div>
<?php endif; ?>
</div>
