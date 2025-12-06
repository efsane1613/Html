<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/auth.php';

function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function logEvent(string $type, string $message): void
{
    global $config;
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('INSERT INTO logs(type, message, created_at) VALUES(?,?,NOW())');
    $stmt->execute([$type, $message]);
}

function refreshGoogleToken(int $userId): ?array
{
    global $config;
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT * FROM google_tokens WHERE user_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$userId]);
    $token = $stmt->fetch();
    if (!$token) {
        return null;
    }
    if (time() < (strtotime($token['created_at']) + (int)$token['expires_in'] - 300)) {
        return $token;
    }

    $payload = [
        'client_id' => $config['google']['client_id'],
        'client_secret' => $config['google']['client_secret'],
        'refresh_token' => $token['refresh_token'],
        'grant_type' => 'refresh_token'
    ];
    $ch = curl_init($config['google']['token_url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    if (!isset($data['access_token'])) {
        logEvent('error', 'Token yenileme hatası: ' . $response);
        return $token;
    }
    $stmt = $pdo->prepare('INSERT INTO google_tokens(user_id, access_token, refresh_token, expires_in, created_at) VALUES(?,?,?,?,NOW())');
    $stmt->execute([
        $userId,
        $data['access_token'],
        $data['refresh_token'] ?? $token['refresh_token'],
        $data['expires_in'] ?? 3600
    ]);
    return [
        'id' => $pdo->lastInsertId(),
        'user_id' => $userId,
        'access_token' => $data['access_token'],
        'refresh_token' => $data['refresh_token'] ?? $token['refresh_token'],
        'expires_in' => $data['expires_in'] ?? 3600,
        'created_at' => date('Y-m-d H:i:s')
    ];
}
