<div yoyo:props="data">
<?php foreach ($data as $id): ?>

    <?php echo Yoyo\yoyo_render('child', ['id' => $id], ['id'=>'child-'.$id]); ?>

<?php endforeach; ?>
</div>