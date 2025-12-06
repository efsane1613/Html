<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/utils.php';

function sendReplyToGoogle(string $accountId, string $locationId, string $reviewId, string $replyText, string $accessToken): bool
{
    $url = "https://mybusiness.googleapis.com/v4/accounts/{$accountId}/locations/{$locationId}/reviews/{$reviewId}:reply";
    $payload = json_encode(['comment' => $replyText]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status >= 200 && $status < 300) {
        return true;
    }
    logEvent('error', 'Google yanıt hatası: ' . $response);
    return false;
}
