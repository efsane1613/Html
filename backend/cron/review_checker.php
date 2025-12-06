<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/utils.php';
require_once __DIR__ . '/../api/fetch_reviews.php';
require_once __DIR__ . '/../api/ai_reply.php';
require_once __DIR__ . '/../api/send_reply.php';

$pdo = Database::getConnection();
$locations = $pdo->query('SELECT l.*, u.id as user_id FROM locations l JOIN users u ON u.id = l.user_id')->fetchAll();

foreach ($locations as $location) {
    $token = refreshGoogleToken((int)$location['user_id']);
    if (!$token) {
        continue;
    }
    $newReviewIds = fetchReviews($location['account_id'], $location['location_id'], $token['access_token'], (int)$location['id']);
    foreach ($newReviewIds as $reviewId) {
        $stmt = $pdo->prepare('SELECT * FROM reviews WHERE id = ?');
        $stmt->execute([$reviewId]);
        $review = $stmt->fetch();
        if (!$review) continue;
        $reply = generateAiReply($review['comment'], $location['business_name']);
        $sent = sendReplyToGoogle($location['account_id'], $location['location_id'], $review['review_id'], $reply, $token['access_token']);
        $status = $sent ? 'sent' : 'failed';
        $stmt = $pdo->prepare('UPDATE reviews SET ai_reply_status = ?, ai_reply_text = ? WHERE id = ?');
        $stmt->execute([$status, $reply, $reviewId]);
    }
}
