<?php foreach ($data as $id): ?>

    <?php echo yoyo_render('child', ['id' => $id], ['id'=>'child-'.$id]); ?>

<?php endforeach; ?>