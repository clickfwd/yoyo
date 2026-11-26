<div <?= $rootAttributes ?>><?php foreach ($rows as $row): ?><p><?= htmlspecialchars($row['title'] ?? '') ?></p><?php endforeach ?></div>
