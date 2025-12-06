<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/utils.php';
require_once __DIR__ . '/../helpers/auth.php';
requireAuth();
logEvent('info', 'PayPal ödeme isteği oluşturuldu kullanıcı: ' . $_SESSION['user_id']);
echo json_encode(['approvalLink' => 'https://www.paypal.com/checkoutnow?token=placeholder']);
