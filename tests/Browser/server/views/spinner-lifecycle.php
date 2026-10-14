<div>
    <span id="<?php echo $scope; ?>-spinner" yoyo:spinning.attr="data-loading">Loading</span>
    <span id="<?php echo $scope; ?>-delayed" yoyo:spinning.class.delay="loading">Delayed</span>
    <span id="<?php echo $scope; ?>-action" yoyo:spinning.class="loading" yoyo:spin-on="skip, fast">Action</span>
    <span id="<?php echo $scope; ?>-remove" class="idle" yoyo:spinning.class.remove="idle">Idle</span>
    <span id="<?php echo $scope; ?>-display" yoyo:spinning>Visible while loading</span>
    <span id="<?php echo $scope; ?>-result"><?php echo $revision; ?></span>
    <button id="<?php echo $scope; ?>-skip" yoyo:get="skip">Skip</button>
    <button id="<?php echo $scope; ?>-fast" yoyo:get="fast">Fast</button>
    <button id="<?php echo $scope; ?>-replace" yoyo:get="update">Replace</button>
    <button id="<?php echo $scope; ?>-none" yoyo:get="update" yoyo:swap="none">No swap</button>
    <button id="<?php echo $scope; ?>-partial" yoyo:get="update"
        yoyo:target="#<?php echo $scope; ?>-result" yoyo:select="#<?php echo $scope; ?>-result">Partial</button>
    <?php if ($scope === 'parent') { ?>
        <?php echo Yoyo\yoyo_render('spinner-lifecycle', ['scope' => 'child'], ['id' => 'child']); ?>
    <?php } ?>
</div>
