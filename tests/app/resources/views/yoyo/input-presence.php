<div>
    <span>result=<?= $result ?></span>
    <span>label=<?= json_encode($label) ?></span>
    <span>collaborator=<?= $collaborator === null ? 'null' : $collaborator->label() ?></span>
    <span>dynamic=<?= json_encode($dynamic) ?></span>
</div>
