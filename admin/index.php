<?php
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../backend/helpers/auth.php';
requireAuth();
requireAdmin();
$pdo = Database::getConnection();
$users = $pdo->query('SELECT id, name, email, plan, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$locations = $pdo->query('SELECT * FROM locations ORDER BY created_at DESC')->fetchAll();
$logs = $pdo->query('SELECT * FROM logs ORDER BY created_at DESC LIMIT 50')->fetchAll();
include __DIR__ . '/../public/includes/header.php';
?>
<div class="card">
    <h2>Admin - Kullanıcılar</h2>
    <table class="table">
        <thead><tr><th>ID</th><th>Ad</th><th>Email</th><th>Plan</th><th>Oluşturma</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id']; ?></td>
                <td><?= htmlspecialchars($u['name']); ?></td>
                <td><?= htmlspecialchars($u['email']); ?></td>
                <td><?= htmlspecialchars($u['plan']); ?></td>
                <td><?= $u['created_at']; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="card">
    <h2>Lokasyonlar</h2>
    <table class="table">
        <thead><tr><th>ID</th><th>Kullanıcı</th><th>İşletme</th><th>Adres</th></tr></thead>
        <tbody>
        <?php foreach ($locations as $l): ?>
            <tr>
                <td><?= $l['id']; ?></td>
                <td><?= $l['user_id']; ?></td>
                <td><?= htmlspecialchars($l['business_name']); ?></td>
                <td><?= htmlspecialchars($l['address']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="card">
    <h2>Loglar</h2>
    <ul>
        <?php foreach ($logs as $log): ?>
            <li><strong><?= $log['type']; ?></strong> - <?= htmlspecialchars($log['message']); ?> (<?= $log['created_at']; ?>)</li>
        <?php endforeach; ?>
    </ul>
</div>
<?php include __DIR__ . '/../public/includes/footer.php'; ?>
