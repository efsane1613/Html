<?php
session_start();

function isAuthenticated(): bool
{
    return isset($_SESSION['user_id']);
}

function requireAuth(): void
{
    if (!isAuthenticated()) {
        header('Location: /public/login.php');
        exit;
    }
}

function requireAdmin(): void
{
    if (!isAuthenticated() || ($_SESSION['role'] ?? 'user') !== 'admin') {
        header('Location: /public/login.php');
        exit;
    }
}
