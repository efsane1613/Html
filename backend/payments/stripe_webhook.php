<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/utils.php';

$payload = file_get_contents('php://input');
logEvent('info', 'Stripe webhook: ' . $payload);
