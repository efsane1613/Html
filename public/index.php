<?php
require __DIR__ . '/../src/autoload.php';

use App\Config\BusinessRepository;
use App\Database\Connection;
use App\Reviews\ReviewRepository;

session_start();

$databaseConfig = require __DIR__ . '/../config/database.php';
$errors = [];
$connectionError = null;
$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

$pdo = null;
$businessRepository = null;
$reviewRepository = null;

try {
    $pdo = Connection::make($databaseConfig);
    $businessRepository = new BusinessRepository($pdo);
    $reviewRepository = new ReviewRepository($pdo);
} catch (\RuntimeException $exception) {
    $connectionError = $exception->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_business') {
    if ($connectionError) {
        $errors[] = 'Veritabanına bağlanılamadığı için işlem gerçekleştirilemedi.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $googleLocation = trim($_POST['google_location'] ?? '');
        $googleAccessToken = trim($_POST['google_access_token'] ?? '');
        $geminiApiKey = trim($_POST['gemini_api_key'] ?? '');
        $geminiModel = trim($_POST['gemini_model'] ?? '');

        if ($name === '') {
            $errors[] = 'İşletme adı gereklidir.';
        }
        if ($googleLocation === '') {
            $errors[] = 'Google konum bilgisi gereklidir.';
        }
        if ($googleAccessToken === '') {
            $errors[] = 'Google erişim jetonu gereklidir.';
        }
        if ($geminiApiKey === '') {
            $errors[] = 'Gemini API anahtarı gereklidir.';
        }

        if (!$errors && $businessRepository) {
            try {
                $businessRepository->create([
                    'name' => $name,
                    'google_location' => $googleLocation,
                    'google_access_token' => $googleAccessToken,
                    'gemini_api_key' => $geminiApiKey,
                    'gemini_model' => $geminiModel !== '' ? $geminiModel : null,
                ]);
                $_SESSION['flash_success'] = 'İşletme başarıyla eklendi.';
                header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
                exit;
            } catch (\RuntimeException $exception) {
                $errors[] = $exception->getMessage();
            }
        }
    }
}

$businesses = $businessRepository ? $businessRepository->all() : [];
$selectedBusinessId = isset($_GET['business_id']) ? (int)$_GET['business_id'] : null;
$selectedBusiness = ($businessRepository && $selectedBusinessId) ? $businessRepository->find($selectedBusinessId) : null;
$reviews = ($reviewRepository && $selectedBusiness) ? $reviewRepository->forBusiness($selectedBusinessId) : [];

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yorum Botu Yönetim Paneli</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Yorum Botu Yönetim Paneli</h1>
            <p>İşletmelerinizi yönetin, çekilen yorumları ve verilen yanıtları görüntüleyin.</p>
        </header>

        <?php if ($connectionError): ?>
            <div class="alert alert-error">
                <strong>Veritabanı bağlantısı kurulamadı:</strong> <?= e($connectionError) ?>
            </div>
        <?php endif; ?>

        <section class="card">
            <h2>Yeni İşletme Ekle</h2>
            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            <form method="post" class="form">
                <input type="hidden" name="action" value="add_business">
                <div class="form-group">
                    <label for="name">İşletme Adı</label>
                    <input type="text" name="name" id="name" required <?= $connectionError ? 'disabled' : '' ?>>
                </div>
                <div class="form-group">
                    <label for="google_location">Google Konum Kimliği</label>
                    <input type="text" name="google_location" id="google_location" placeholder="accounts/.../locations/..." required <?= $connectionError ? 'disabled' : '' ?>>
                </div>
                <div class="form-group">
                    <label for="google_access_token">Google Access Token</label>
                    <textarea name="google_access_token" id="google_access_token" rows="3" required <?= $connectionError ? 'disabled' : '' ?>></textarea>
                </div>
                <div class="form-group">
                    <label for="gemini_api_key">Gemini API Anahtarı</label>
                    <input type="text" name="gemini_api_key" id="gemini_api_key" required <?= $connectionError ? 'disabled' : '' ?>>
                </div>
                <div class="form-group">
                    <label for="gemini_model">Gemini Modeli (opsiyonel)</label>
                    <input type="text" name="gemini_model" id="gemini_model" placeholder="models/gemini-1.0-pro" <?= $connectionError ? 'disabled' : '' ?>>
                </div>
                <button type="submit" <?= $connectionError ? 'disabled' : '' ?>>İşletmeyi Kaydet</button>
            </form>
        </section>

        <section class="card">
            <h2>İşletme Listesi</h2>
            <?php if (!$businesses): ?>
                <p>Henüz işletme eklenmedi.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Adı</th>
                            <th>Google Konumu</th>
                            <th>Oluşturulma</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($businesses as $business): ?>
                            <tr class="<?= $selectedBusiness && $selectedBusiness['id'] === $business['id'] ? 'active' : '' ?>">
                                <td><?= e($business['name']) ?></td>
                                <td><code><?= e($business['googleLocation']) ?></code></td>
                                <td><?= e($business['createdAt']) ?></td>
                                <td><a href="?business_id=<?= $business['id'] ?>">Yorumları Gör</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <?php if ($selectedBusiness): ?>
            <section class="card">
                <h2><?= e($selectedBusiness['name']) ?> - Yorumlar</h2>
                <?php if (!$reviews): ?>
                    <p>Bu işletme için henüz kayıtlı yorum bulunmuyor.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Müşteri</th>
                                <th>Puan</th>
                                <th>Yorum</th>
                                <th>Yorum Tarihi</th>
                                <th>Yanıt</th>
                                <th>Yanıt Tarihi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $review): ?>
                                <tr>
                                    <td><?= e($review['reviewer_name'] ?? 'Bilinmiyor') ?></td>
                                    <td><?= $review['rating'] !== null ? str_repeat('★', (int)$review['rating']) : '-' ?></td>
                                    <td><?= nl2br(e($review['comment'])) ?></td>
                                    <td><?= e($review['review_update_time']) ?></td>
                                    <td><?= $review['reply_text'] ? nl2br(e($review['reply_text'])) : '<span class="badge badge-warning">Yanıt Bekliyor</span>' ?></td>
                                    <td><?= e($review['replied_at'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>
