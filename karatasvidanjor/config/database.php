<?php
/**
 * Karataş Vidanjör - Veritabanı Bağlantı Dosyası
 */

// Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'karatasvidanjor');
define('DB_USER', 'root'); // Production'da bu değerler değiştirilmeli
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Veritabanı bağlantısı
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die('Veritabanı bağlantı hatası: ' . $e->getMessage());
}

// Site ayarları
function getSiteSetting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

// Güvenli input temizleme
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Email validasyonu
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Telefon validasyonu
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 15;
}
?>