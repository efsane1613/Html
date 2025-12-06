<?php
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../backend/helpers/auth.php';
require_once __DIR__ . '/../backend/helpers/utils.php';
requireAuth();
$pdo = Database::getConnection();
$userId = $_SESSION['user_id'];

$stats = [
    'reviews' => 0,
    'ai_replies' => 0,
    'locations' => 0,
];

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM reviews WHERE location_id IN (SELECT id FROM locations WHERE user_id = ?)');
$stmt->execute([$userId]);
$stats['reviews'] = (int)$stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reviews WHERE ai_reply_status = 'sent' AND location_id IN (SELECT id FROM locations WHERE user_id = ?)");
$stmt->execute([$userId]);
$stats['ai_replies'] = (int)$stmt->fetch()['total'];

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM locations WHERE user_id = ?');
$stmt->execute([$userId]);
$stats['locations'] = (int)$stmt->fetch()['total'];

$reviews = $pdo->prepare('SELECT r.*, l.business_name FROM reviews r JOIN locations l ON r.location_id = l.id WHERE l.user_id = ? ORDER BY r.review_time DESC LIMIT 5');
$reviews->execute([$userId]);
$recentReviews = $reviews->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="grid">
    <div class="card">
        <p class="badge">Toplam Yorum</p>
        <h2><?= $stats['reviews']; ?></h2>
    </div>
    <div class="card">
        <p class="badge">AI Yanıtları</p>
        <h2><?= $stats['ai_replies']; ?></h2>
    </div>
    <div class="card">
        <p class="badge">Lokasyonlar</p>
        <h2><?= $stats['locations']; ?></h2>
    </div>
</div>
<div class="card">
    <h3>Son Yorumlar</h3>
    <table class="table">
        <thead><tr><th>İşletme</th><th>Müşteri</th><th>Puan</th><th>Yorum</th><th>Durum</th></tr></thead>
        <tbody>
        <?php foreach ($recentReviews as $review): ?>
            <tr>
                <td><?= htmlspecialchars($review['business_name']); ?></td>
                <td><?= htmlspecialchars($review['reviewer_name']); ?></td>
                <td><?= htmlspecialchars($review['star_rating']); ?>⭐</td>
                <td><?= htmlspecialchars($review['comment']); ?></td>
                <td><span class="badge"><?= htmlspecialchars($review['ai_reply_status'] ?: 'beklemede'); ?></span></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($recentReviews)): ?>
            <tr><td colspan="5">Henüz yorum bulunmuyor.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
