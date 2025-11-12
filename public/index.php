<?php
require __DIR__ . '/../src/autoload.php';

use App\Config\BusinessRepository;
use App\Database\Connection;
use App\Reviews\ReviewRepository;

session_start();

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php?logged_out=1');
    exit;
}

if (empty($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit;
}

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
$businessStats = $reviewRepository ? $reviewRepository->statsByBusiness() : [];
$selectedStats = ($reviewRepository && $selectedBusiness)
    ? $reviewRepository->statsForBusiness($selectedBusinessId)
    : [
        'total' => 0,
        'replied' => 0,
        'pending' => 0,
        'last_review_time' => null,
        'last_reply_time' => null,
    ];

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function mask_token(?string $value, int $prefix = 4, int $suffix = 4): string
{
    $value = (string)$value;
    if ($value === '') {
        return '';
    }

    $length = strlen($value);
    if ($length <= $prefix + $suffix) {
        return str_repeat('•', max(1, $length));
    }

    $maskedLength = $length - ($prefix + $suffix);

    return substr($value, 0, $prefix) . str_repeat('•', $maskedLength) . substr($value, -$suffix);
}

function format_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }

    try {
        return (new DateTimeImmutable($value))->format('d.m.Y H:i');
    } catch (Exception $exception) {
        return $value;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yorum Botu Yönetim Paneli</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="app-shell">
        <header class="app-header">
            <div class="brand">
                <div class="brand__icon">🤖</div>
                <div class="brand__text">
                    <span class="brand__title">Yorum Botu</span>
                    <span class="brand__subtitle">Otomatik Yorum Yönetim Paneli</span>
                </div>
            </div>
            <div class="header-meta">
                <span class="meta-pill">⏱ Cron: 1 dk</span>
                <span class="meta-pill">İzlenen işletme: <?= count($businesses) ?></span>
                <?php if ($selectedBusiness): ?>
                    <span class="meta-pill meta-pill--active">Seçili: <?= e($selectedBusiness['name']) ?></span>
                <?php endif; ?>
            </div>
            <div class="header-actions">
                <a href="?logout=1" class="logout-button" aria-label="Çıkış yap">Çıkış Yap</a>
            </div>
        </header>

        <main class="content">
            <?php if ($connectionError): ?>
                <div class="alert alert-error">
                    <strong>Veritabanı bağlantısı kurulamadı:</strong> <?= e($connectionError) ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <section class="panel panel--form">
                    <div class="panel__header">
                        <div>
                            <h2>Yeni İşletme Ekle</h2>
                            <p>Google My Business ve Gemini bilgilerini girerek otomatik yanıtı başlat.</p>
                        </div>
                    </div>

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
                            <input type="text" name="name" id="name" placeholder="Örn: Poyraz Halı Yıkama" required <?= $connectionError ? 'disabled' : '' ?>>
                        </div>
                        <div class="form-group">
                            <label for="google_location">Google Konum Kimliği</label>
                            <input type="text" name="google_location" id="google_location" placeholder="accounts/.../locations/..." required <?= $connectionError ? 'disabled' : '' ?>>
                        </div>
                        <div class="form-group">
                            <label for="google_access_token">Google Access Token</label>
                            <textarea name="google_access_token" id="google_access_token" rows="3" placeholder="OAuth erişim jetonunu buraya gir" required <?= $connectionError ? 'disabled' : '' ?>></textarea>
                        </div>
                        <div class="form-group">
                            <label for="gemini_api_key">Gemini API Anahtarı</label>
                            <input type="text" name="gemini_api_key" id="gemini_api_key" placeholder="AI anahtarını buraya gir" required <?= $connectionError ? 'disabled' : '' ?>>
                        </div>
                        <div class="form-group">
                            <label for="gemini_model">Gemini Modeli (opsiyonel)</label>
                            <input type="text" name="gemini_model" id="gemini_model" placeholder="Örn: gemini-2.5-flash-lite-preview-09-2025" <?= $connectionError ? 'disabled' : '' ?>>
                        </div>
                        <button type="submit" <?= $connectionError ? 'disabled' : '' ?>>İşletmeyi Kaydet</button>
                    </form>
                </section>

                <section class="panel panel--list">
                    <div class="panel__header">
                        <div>
                            <h2>İşletme Listesi</h2>
                            <p>Eklediğin tüm işletmeleri ve bağlantılı Google konum kimliklerini görüntüle.</p>
                        </div>
                    </div>

                    <?php if (!$businesses): ?>
                        <div class="empty-state">
                            <h3>Henüz işletme eklenmedi</h3>
                            <p>İlk işletmeni ekleyerek otomatik yorum yanıtlamayı başlatabilirsin.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>İşletme</th>
                                        <th>Bağlantı Bilgileri</th>
                                        <th>Yorum Durumu</th>
                                        <th>Son Kontroller</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($businesses as $business): ?>
                                        <?php
                                        $stats = $businessStats[$business['id']] ?? [
                                            'total' => 0,
                                            'replied' => 0,
                                            'pending' => 0,
                                            'last_review_time' => null,
                                            'last_reply_time' => null,
                                        ];
                                        ?>
                                        <tr class="<?= $selectedBusiness && $selectedBusiness['id'] === $business['id'] ? 'is-active' : '' ?>">
                                            <td>
                                                <span class="table-title"><?= e($business['name']) ?></span>
                                                <span class="table-subtitle">Eklenme: <?= format_datetime($business['createdAt'] ?? null) ?></span>
                                            </td>
                                            <td>
                                                <dl class="definition-list">
                                                    <div class="definition-list__item">
                                                        <dt>Google Konum</dt>
                                                        <dd><code><?= e($business['googleLocation']) ?></code></dd>
                                                    </div>
                                                    <div class="definition-list__item">
                                                        <dt>Google Token</dt>
                                                        <dd><code title="<?= e($business['googleAccessToken']) ?>"><?= e(mask_token($business['googleAccessToken'])) ?></code></dd>
                                                    </div>
                                                    <div class="definition-list__item">
                                                        <dt>Gemini API</dt>
                                                        <dd><code title="<?= e($business['geminiApiKey']) ?>"><?= e(mask_token($business['geminiApiKey'])) ?></code></dd>
                                                    </div>
                                                    <div class="definition-list__item">
                                                        <dt>Model</dt>
                                                        <dd><?= e($business['geminiModel']) ?></dd>
                                                    </div>
                                                </dl>
                                            </td>
                                            <td>
                                                <div class="stat-chip">
                                                    <span class="stat-chip__value"><?= $stats['total'] ?></span>
                                                    <span class="stat-chip__label">Toplam</span>
                                                </div>
                                                <div class="stat-chip stat-chip--success">
                                                    <span class="stat-chip__value"><?= $stats['replied'] ?></span>
                                                    <span class="stat-chip__label">Yanıtlanan</span>
                                                </div>
                                                <div class="stat-chip stat-chip--warning">
                                                    <span class="stat-chip__value"><?= $stats['pending'] ?></span>
                                                    <span class="stat-chip__label">Bekleyen</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="table-title">Son Cron: <?= format_datetime($business['lastCheckedAt'] ?? null) ?></div>
                                                <div class="table-subtitle">Son Google Yorumu: <?= format_datetime($stats['last_review_time'] ?? null) ?></div>
                                                <div class="table-subtitle">Son Yanıt: <?= format_datetime($stats['last_reply_time'] ?? null) ?></div>
                                                <div class="table-subtitle">Son Çekilen Adet: <?= $business['lastCheckFetched'] ?></div>
                                                <div class="table-subtitle">Son Yanıtlanan Adet: <?= $business['lastCheckReplied'] ?></div>
                                            </td>
                                            <td><a class="link" href="?business_id=<?= $business['id'] ?>">Yorumları Gör</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <?php if ($selectedBusiness): ?>
                <section class="panel panel--reviews">
                    <div class="panel__header">
                        <div>
                            <h2><?= e($selectedBusiness['name']) ?> İşletmesi - Yorumlar</h2>
                            <p>Çekilen yorumlar ve bot tarafından verilen yanıtlar burada listelenir.</p>
                        </div>
                        <div class="panel__meta">
                            <span class="meta-pill">Toplam: <?= $selectedStats['total'] ?></span>
                            <span class="meta-pill meta-pill--success">Yanıtlanan: <?= $selectedStats['replied'] ?></span>
                            <span class="meta-pill meta-pill--warning">Bekleyen: <?= $selectedStats['pending'] ?></span>
                            <span class="meta-pill">Son Çekilen: <?= $selectedBusiness['lastCheckFetched'] ?? 0 ?></span>
                            <span class="meta-pill">Son Yanıtlanan: <?= $selectedBusiness['lastCheckReplied'] ?? 0 ?></span>
                            <span class="meta-pill">Son Google Yorumu: <?= format_datetime($selectedStats['last_review_time'] ?? null) ?></span>
                            <span class="meta-pill">Son Yanıt: <?= format_datetime($selectedStats['last_reply_time'] ?? null) ?></span>
                            <span class="meta-pill meta-pill--active">Son Kontrol: <?= format_datetime($selectedBusiness['lastCheckedAt'] ?? null) ?></span>
                        </div>
                    </div>

                    <?php if (!$reviews): ?>
                        <div class="empty-state">
                            <h3>Henüz yorum kaydı yok</h3>
                            <p>Cron job çalıştıkça yeni yorumlar otomatik olarak burada görünecektir.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper table-wrapper--scroll">
                            <table class="table table--reviews">
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
                                            <td>
                                                <?php if ($review['rating'] !== null): ?>
                                                    <span class="rating" aria-label="<?= (int) $review['rating'] ?> yıldız">
                                                        <?= str_repeat('★', (int) $review['rating']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= nl2br(e($review['comment'])) ?></td>
                                            <td><?= e($review['review_update_time']) ?></td>
                                            <td>
                                                <?php if ($review['reply_text']): ?>
                                                    <?= nl2br(e($review['reply_text'])) ?>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Yanıt Bekliyor</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= e($review['replied_at'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
