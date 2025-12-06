<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/utils.php';

function fetchReviews(string $accountId, string $locationId, string $accessToken, int $dbLocationId): array
{
    $url = "https://mybusiness.googleapis.com/v4/accounts/{$accountId}/locations/{$locationId}/reviews";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    if (!isset($data['reviews'])) {
        logEvent('info', 'Yeni yorum bulunamadı: ' . $response);
        return [];
    }
    $pdo = Database::getConnection();
    $newReviews = [];
    foreach ($data['reviews'] as $review) {
        $stmt = $pdo->prepare('SELECT id FROM reviews WHERE review_id = ?');
        $stmt->execute([$review['reviewId']]);
        if ($stmt->fetch()) {
            continue;
        }
        $stmt = $pdo->prepare('INSERT INTO reviews(location_id, review_id, reviewer_name, star_rating, comment, review_time, ai_reply_status, created_at) VALUES(?,?,?,?,?,?,?,NOW())');
        $stmt->execute([
            $dbLocationId,
            $review['reviewId'],
            $review['reviewer']['displayName'] ?? 'Bilinmiyor',
            $review['starRating'] ?? 0,
            $review['comment'] ?? '',
            $review['createTime'] ?? date('Y-m-d H:i:s'),
            'pending'
        ]);
        $newReviews[] = $pdo->lastInsertId();
    }
    return $newReviews;
}
