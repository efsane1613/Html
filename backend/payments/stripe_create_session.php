<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/utils.php';
require_once __DIR__ . '/../helpers/auth.php';
requireAuth();

$plan = $_GET['plan'] ?? 'basic';
$prices = ['basic' => 9900, 'pro' => 19900];
$amount = $prices[$plan] ?? 9900;
logEvent('info', 'Stripe ödeme oturumu oluşturuldu kullanıcı: ' . $_SESSION['user_id']);
echo json_encode(['checkoutUrl' => 'https://stripe.com/checkout-placeholder', 'amount' => $amount]);
