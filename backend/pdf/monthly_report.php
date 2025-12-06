<?php
$config = require __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
requireAuth();

$pdo = Database::getConnection();
$userId = $_SESSION['user_id'];
$stats = $pdo->prepare('SELECT COUNT(*) as total, SUM(ai_reply_status = "sent") as replied FROM reviews WHERE location_id IN (SELECT id FROM locations WHERE user_id = ?)');
$stats->execute([$userId]);
$data = $stats->fetch();

$html = '<h1>Google Review Bot Aylık Rapor</h1>';
$html .= '<p>Toplam yorum: ' . ($data['total'] ?? 0) . '</p>';
$html .= '<p>AI yanıtları: ' . ($data['replied'] ?? 0) . '</p>';
$html .= '<p>Raporu mpdf ile PDF olarak oluşturmak için composer ile mpdf/mpdf kurulumunu yapın.</p>';

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($html);
    $mpdf->Output('rapor.pdf', 'I');
    exit;
}

echo $html;
