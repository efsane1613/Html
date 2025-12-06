<?php
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../backend/helpers/auth.php';
require_once __DIR__ . '/../backend/helpers/utils.php';
requireAuth();

if (!isset($_GET['code'])) {
    echo 'Kod bulunamadı';
    exit;
}

$code = $_GET['code'];
$payload = [
    'code' => $code,
    'client_id' => $config['google']['client_id'],
    'client_secret' => $config['google']['client_secret'],
    'redirect_uri' => $config['google']['redirect_uri'],
    'grant_type' => 'authorization_code'
];
$ch = curl_init($config['google']['token_url']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
$response = curl_exec($ch);
curl_close($ch);
$data = json_decode($response, true);

if (!isset($data['access_token'])) {
    logEvent('error', 'Google token hatası: ' . $response);
    echo 'Token alınamadı';
    exit;
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare('INSERT INTO google_tokens(user_id, access_token, refresh_token, expires_in, created_at) VALUES(?,?,?,?,NOW())');
$stmt->execute([
    $_SESSION['user_id'],
    $data['access_token'],
    $data['refresh_token'] ?? '',
    $data['expires_in'] ?? 3600
]);
logEvent('info', 'Google token kaydedildi kullanıcı: ' . $_SESSION['user_id']);
header('Location: /public/dashboard.php');
