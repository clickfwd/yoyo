<?php

require __DIR__.'/../layout.php';

// Simulated product data — each row gets its own Yoyo components
$products = [
    ['id' => 1, 'name' => 'Widget Alpha', 'status' => 'active'],
    ['id' => 2, 'name' => 'Widget Beta',  'status' => 'draft'],
    ['id' => 3, 'name' => 'Widget Gamma', 'status' => 'archived'],
];

ob_start();
?>
<table id="product-list">
    <thead><tr><th>Name</th><th>Favorite</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($products as $product): ?>
        <tr data-product="<?php echo $product['id']; ?>">
            <td><?php echo $product['name']; ?></td>
            <td>
                <?php echo Yoyo\yoyo_render('favorite-button', [
                    'itemId' => $product['id'],
                    'isFavorited' => 0,
                ], ['id' => 'fav-'.$product['id']]); ?>
            </td>
            <td>
                <?php echo Yoyo\yoyo_render('status-dropdown', [
                    'itemId' => $product['id'],
                    'status' => $product['status'],
                ], ['id' => 'status-'.$product['id']]); ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php

render_page('Product List', ob_get_clean());
