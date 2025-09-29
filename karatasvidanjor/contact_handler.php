<?php
/**
 * Karataş Vidanjör - İletişim Formu İşleyicisi
 */

header('Content-Type: application/json');

// Sürekli kontrol değişkenleri (gerçek API anahtarı olmadığı için şimdilik kapalı  tutuyoruz)
$googleApiKey = 'AIzaSyBvOkBwJjOkBwJjOkBwJjOkBwJjOkBwJjOk'; // Production'da gerçek API key kullanılacak

// AJAX isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit;
}

// Veritabanı bağlantısını dahil et
require_once 'config/database.php';

// Form verilerini al ve temizle
$name = sanitizeInput($_POST['name'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$phone = sanitizeInput($_POST['phone'] ?? '');
$service = sanitizeInput($_POST['service'] ?? '');
$message = sanitizeInput($_POST['message'] ?? '');
$privacy = isset($_POST['privacy']) ? true : false;

// Validasyon
$errors = [];

// Ad soyad kontrolü
if (empty($name)) {
    $errors[] = 'Ad Soyad alanı zorunludur.';
} elseif (strlen($name) < 2) {
    $errors[] = 'Ad Soyad en az 2 karakter olmalıdır.';
}

// Email kontrolü
if (empty($email)) {
    $errors[] = 'E-mail alanı zorunludur.';
} elseif (!validateEmail($email)) {
    $errors[] = 'Geçerli bir e-mail adresi girin.';
}

// Telefon kontrolü
if (empty($phone)) {
    $errors[] = 'Telefon alanı zorunludur.';
} elseif (!validatePhone($phone)) {
    $errors[] = 'Geçerli bir telefon numarası girin.';
}

// Mesaj kontrolü
if (empty($message)) {
    $errors[] = 'Mesaj alanı zorunludur.';
} elseif (strlen($message) < 10) {
    $errors[] = 'Mesaj en az 10 karakter olmalıdır.';
}

// Gizlilik kontrolü
if (!$privacy) {
    $errors[] = 'Kişisel verilerin korunması şartlarını kabul etmelisiniz.';
}

// Hata varsa json response döndür
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors)
    ]);
    exit;
}

// Mesajı veritabanına kaydet
try {
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (name, email, phone, message, service_type, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'new', NOW())
    ");
    
    $result = $stmt->execute([$name, $email, $phone, $message, $service]);
    
    if ($result) {
        // Email bildirimi gönder (opsiyonel - basit mail sistemi)
        $adminEmail = 'karatasvidanjor@gmail.com';
        $subject = 'Yeni İletişim Mesajı - Karatas Vidanjör';
        $emailBody = "
        Yeni bir iletişim mesajı alındı:
        
        Ad Soyad: {$name}
        E-mail: {$email}
        Telefon: {$phone}
        Hizmet Türü: " . ($service ? $service : 'Belirtilmemiş') . "
        
        Mesaj:
        {$message}
        
        Tarih: " . date('d.m.Y H:i:s') . "
        ";
        
        // Basit mail gönderimi (Production'da PHPMailer kullanılmalı)
        $headers = "From: noreply@karatasvidanjor.com\r\n";
        $headers .= "Reply-To: {$email}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        mail($adminEmail, $subject, $emailBody, $headers);
        
        echo json_encode([
            'success' => true,
            'message' => 'Mesajınız başarıyla gönderildi! En kısa sürede size dönüş yapacağız.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Mesaj gönderilirken bir hata oluştu.'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: Mesaj gönderilemedi.'
    ]);
}

// Şimdilik loglama için dosya kayıt sistemi (Production'da loglama sistemi kullanılmalı)
$logData = [
    'date' => date('Y-m-d H:i:s'),
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'service' => $service,
    'message' => substr($message, 0, 100) . '...',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
];

file_put_contents('contact_logs.txt', json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
?>