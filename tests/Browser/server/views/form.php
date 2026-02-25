<?php if ($success): ?>
    <div id="form" data-success>
        <p>Thank you for registering!</p>
    </div>
<?php else: ?>
    <form id="form" yoyo:post="register" yoyo:on="submit">
        <div>
            <label for="name">Name</label>
            <input id="name" name="name" type="text"
                value="<?php echo htmlspecialchars($name ?? ''); ?>" />
            <?php if (! empty($errors['name'])): ?>
                <span data-error="name"><?php echo $errors['name']; ?></span>
            <?php endif; ?>
        </div>

        <div>
            <label for="email">Email</label>
            <input id="email" name="email" type="email"
                value="<?php echo htmlspecialchars($email ?? ''); ?>" />
            <?php if (! empty($errors['email'])): ?>
                <span data-error="email"><?php echo $errors['email']; ?></span>
            <?php endif; ?>
        </div>

        <button type="submit">Submit</button>
    </form>
<?php endif; ?>
