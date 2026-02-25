<div data-component="modal-trigger">
    <button data-action="open" yoyo:get="openModal">Open Modal</button>
<?php if ($isOpen): ?>
    <dialog data-modal open>
        <h2 data-modal-title>Modal Content</h2>
        <p>This is the modal body.</p>
        <button data-action="close" yoyo:get="closeModal">Close</button>
    </dialog>
<?php endif; ?>
</div>
