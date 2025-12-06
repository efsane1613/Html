<?php
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../backend/helpers/auth.php';
requireAuth();

$params = [
    'client_id' => $config['google']['client_id'],
    'redirect_uri' => $config['google']['redirect_uri'],
    'response_type' => 'code',
    'scope' => $config['google']['scope'],
    'access_type' => 'offline',
    'include_granted_scopes' => 'true',
    'prompt' => 'consent'
];
$authUrl = $config['google']['auth_url'] . '?' . http_build_query($params);
header('Location: ' . $authUrl);
exit;
