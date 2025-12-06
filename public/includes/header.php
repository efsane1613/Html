<?php
require_once __DIR__ . '/../../config/config.php';
$config = require __DIR__ . '/../../config/config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['app_name']); ?></title>
    <link rel="stylesheet" href="/public/assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="logo">Google Review Bot</div>
    <nav>
        <a href="/public/dashboard.php">Panel</a>
        <a href="/public/google_login.php">Google Bağlantısı</a>
        <a href="/public/logout.php">Çıkış</a>
    </nav>
</header>
<main class="container">
