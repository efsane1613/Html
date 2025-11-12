<?php
require __DIR__ . '/../src/autoload.php';

use App\Config\BusinessRepository;
use App\Database\Connection;
use App\Google\GoogleMyBusinessClient;
use App\Google\GoogleOAuthClient;
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
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$pdo = null;
$businessRepository = null;
$reviewRepository = null;

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$protocol = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDir = str_replace('\\', '/', dirname($scriptName));
if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
    $scriptDir = '';
}
$defaultBasePath = rtrim($scriptDir, '/');
$defaultRedirectUri = rtrim($protocol . '://' . $host . $defaultBasePath, '/') . '/oauth/callback.php';
$defaultJavascriptOrigin = $protocol . '://' . $host;

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
        $googleClientId = trim($_POST['google_client_id'] ?? '');
        $googleClientSecret = trim($_POST['google_client_secret'] ?? '');
        $googleRedirectUri = trim($_POST['google_redirect_uri'] ?? $defaultRedirectUri);
        $googleJavascriptOrigin = trim($_POST['google_javascript_origin'] ?? $defaultJavascriptOrigin);
        $authorizationCode = trim($_POST['authorization_code'] ?? '');
        $geminiApiKey = trim($_POST['gemini_api_key'] ?? '');
        $geminiModel = trim($_POST['gemini_model'] ?? '');

        if ($name === '') {
            $errors[] = 'İşletme adı gereklidir.';
        }
        if ($googleLocation === '') {
            $errors[] = 'Google konum bilgisi gereklidir.';
        }
        if ($googleClientId === '') {
            $errors[] = 'Google OAuth Client ID gereklidir.';
        }
        if ($googleClientSecret === '') {
            $errors[] = 'Google OAuth Client Secret gereklidir.';
        }
        if ($googleRedirectUri === '' || !filter_var($googleRedirectUri, FILTER_VALIDATE_URL)) {
            $errors[] = 'Google OAuth Redirect URI geçerli bir URL olmalıdır.';
        }
        if ($googleJavascriptOrigin === '' || !filter_var($googleJavascriptOrigin, FILTER_VALIDATE_URL)) {
            $errors[] = 'Authorized JavaScript Origin geçerli bir URL olmalıdır.';
        }
        if ($geminiApiKey === '') {
            $errors[] = 'Gemini API anahtarı gereklidir.';
        }

        if (!$errors && $businessRepository) {
            try {
                $businessId = $businessRepository->create([
                    'name' => $name,
                    'google_location' => $googleLocation,
                    'google_client_id' => $googleClientId,
                    'google_client_secret' => $googleClientSecret,
                    'google_oauth_redirect_uri' => $googleRedirectUri,
                    'google_oauth_javascript_origin' => $googleJavascriptOrigin,
                    'gemini_api_key' => $geminiApiKey,
                    'gemini_model' => $geminiModel !== '' ? $geminiModel : null,
                    'connection_status' => 'pending',
                    'connection_message' => $authorizationCode !== ''
                        ? 'Yetkilendirme kodu işlendi. Sonuç bekleniyor.'
                        : 'Google yetkilendirmesi bekleniyor. "Bağlantıyı Test Et" butonu ile yetkilendirme kodu girin.',
                ]);

                if ($authorizationCode !== '') {
                    try {
                        $oauthClient = new GoogleOAuthClient($googleClientId, $googleClientSecret, $googleRedirectUri);
                        $tokenResponse = $oauthClient->exchangeAuthorizationCode($authorizationCode);

                        $expiresAt = null;
                        if (isset($tokenResponse['expires_in'])) {
                            $expiresAt = (new DateTimeImmutable())
                                ->add(new DateInterval('PT' . max(0, (int)$tokenResponse['expires_in']) . 'S'))
                                ->format('Y-m-d H:i:s');
                        }

                        $businessRepository->updateTokens(
                            $businessId,
                            $tokenResponse['access_token'],
                            $tokenResponse['refresh_token'] ?? null,
                            $expiresAt
                        );

                        $businessRepository->updateConnectionStatus($businessId, 'connected', 'Google OAuth yetkilendirmesi başarıyla tamamlandı.');
                        $_SESSION['flash_success'] = 'İşletme ve Google bağlantısı başarıyla kaydedildi.';
                    } catch (\Throwable $exception) {
                        $businessRepository->updateConnectionStatus($businessId, 'error', $exception->getMessage());
                        $_SESSION['flash_success'] = 'İşletme kaydedildi ancak Google bağlantısı doğrulanamadı.';
                        $_SESSION['flash_error'] = 'Google OAuth hatası: ' . $exception->getMessage();
                    }
                } else {
                    $businessRepository->updateConnectionStatus(
                        $businessId,
                        'pending',
                        'Google yetkilendirmesi bekleniyor. "Bağlantıyı Test Et" butonu ile yetkilendirme kodu girin.'
                    );
                    $_SESSION['flash_success'] = 'İşletme kaydedildi. Google yetkilendirmesini tamamlamak için "Bağlantıyı Test Et" butonunu kullanın.';
                }

                header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
                exit;
            } catch (\RuntimeException $exception) {
                $errors[] = $exception->getMessage();
            }
        }
    }
}

$redirectBase = strtok($_SERVER['REQUEST_URI'], '?');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'test_connection') {
    $businessId = isset($_POST['business_id']) ? (int)$_POST['business_id'] : 0;

    if ($connectionError) {
        $_SESSION['flash_error'] = 'Veritabanı bağlantısı olmadığı için test gerçekleştirilemedi.';
        header('Location: ' . $redirectBase);
        exit;
    }

    if (!$businessRepository || !$businessId) {
        $_SESSION['flash_error'] = 'Geçersiz işletme isteği.';
        header('Location: ' . $redirectBase);
        exit;
    }

    $authorizationCode = trim($_POST['authorization_code'] ?? '');

    try {
        $business = $businessRepository->find($businessId);
        if (!$business) {
            throw new RuntimeException('İşletme kaydı bulunamadı.');
        }

        $oauthClient = new GoogleOAuthClient($business['googleClientId'], $business['googleClientSecret'], $business['googleRedirectUri'] ?? null);
        $tokenResponse = null;

        if ($authorizationCode !== '') {
            $tokenResponse = $oauthClient->exchangeAuthorizationCode($authorizationCode);
        } elseif (!empty($business['googleRefreshToken'])) {
            $tokenResponse = $oauthClient->refreshAccessToken($business['googleRefreshToken']);
        }

        $accessToken = $business['googleAccessToken'] ?? '';

        if ($tokenResponse !== null) {
            $expiresAt = null;
            if (isset($tokenResponse['expires_in'])) {
                $expiresAt = (new DateTimeImmutable())
                    ->add(new DateInterval('PT' . max(0, (int)$tokenResponse['expires_in']) . 'S'))
                    ->format('Y-m-d H:i:s');
            }

            $businessRepository->updateTokens(
                $businessId,
                $tokenResponse['access_token'],
                $tokenResponse['refresh_token'] ?? null,
                $expiresAt
            );

            $accessToken = $tokenResponse['access_token'];
        }

        if ($accessToken === '') {
            throw new RuntimeException('Geçerli bir erişim jetonu bulunamadı. "Yetkilendirme Linki" ile Google\'a izin verip oluşan kodu girin.');
        }

        $googleClient = new GoogleMyBusinessClient($accessToken);
        $reviews = $googleClient->listReviews($business['googleLocation']);
        $resolvedLocation = $googleClient->getLastResolvedLocationName();
        $locationNote = '';
        $syncedCount = 0;
        $syncErrors = [];

        if ($resolvedLocation && $resolvedLocation !== $business['googleLocation']) {
            $businessRepository->updateGoogleLocation($businessId, $resolvedLocation);
            $business['googleLocation'] = $resolvedLocation;
            $locationNote = sprintf(" Konum kaydı \"%s\" olarak güncellendi.", $resolvedLocation);
        }

        if ($reviewRepository) {
            foreach ($reviews as $review) {
                try {
                    $storedReview = $reviewRepository->upsertReview($businessId, $review);

                    if (!empty($review['reviewReply']['comment'])) {
                        $reviewRepository->recordReply(
                            $storedReview['id'],
                            (string)$review['reviewReply']['comment'],
                            $review['reviewReply']['updateTime'] ?? $review['reviewReply']['createTime'] ?? null,
                            'google'
                        );
                    }

                    $syncedCount++;
                } catch (\Throwable $reviewException) {
                    $syncErrors[] = $reviewException->getMessage();
                }
            }
        }

        $statusMessage = sprintf('Bağlantı başarılı. %d adet yorum okunabildi.', count($reviews));
        if ($locationNote !== '') {
            $statusMessage .= ' Konum kaydı doğrulandı.';
        }
        if ($reviewRepository) {
            $statusMessage .= sprintf(' %d yorum panel ile senkronize edildi.', $syncedCount);
            if ($syncErrors !== []) {
                $statusMessage .= ' Bazı yorumlar kaydedilirken hata oluştu.';
            }
        }

        $businessRepository->updateConnectionStatus(
            $businessId,
            'connected',
            $statusMessage
        );

        $_SESSION['flash_success'] = 'Google bağlantısı başarıyla test edildi.' . $locationNote;
        if ($authorizationCode !== '') {
            $_SESSION['flash_success'] .= ' Yeni jetonlar kaydedildi.';
        }
        if ($reviewRepository) {
            $_SESSION['flash_success'] .= sprintf(' %d yorum panelde güncellendi.', $syncedCount);
        }
        if ($syncErrors !== []) {
            $_SESSION['flash_error'] = 'Bazı yorumlar kaydedilemedi: ' . $syncErrors[0];
        }
    } catch (\Throwable $exception) {
        $businessRepository?->updateConnectionStatus($businessId, 'error', $exception->getMessage());
        $_SESSION['flash_error'] = 'Bağlantı testi başarısız: ' . $exception->getMessage();
    }

    header('Location: ' . $redirectBase . ($businessId ? '?business_id=' . $businessId : ''));
    exit;
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

/**
 * @return array{label:string,class:string}
 */
function connection_status_meta(?string $status): array
{
    $status = strtolower((string)$status);

    switch ($status) {
        case 'connected':
            return ['label' => 'Bağlı', 'class' => 'status-badge status-badge--success'];
        case 'pending':
            return ['label' => 'Beklemede', 'class' => 'status-badge status-badge--warning'];
        case 'error':
            return ['label' => 'Hata', 'class' => 'status-badge status-badge--error'];
        case 'never':
        default:
            return ['label' => 'Test edilmedi', 'class' => 'status-badge'];
    }
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

                    <?php if ($flashError): ?>
                        <div class="alert alert-error"><?= e($flashError) ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= e($success) ?></div>
                    <?php endif; ?>

                    <div class="alert alert-info">
                        <strong>Google OAuth hatırlatması:</strong> OAuth istemcisi test modundaysa Google Cloud Console &rarr; <em>OAuth consent screen</em> sayfasındaki <em>Test users</em> bölümüne yetkilendirme yapacak Google hesaplarını ekleyin. Alan doğrulamasını tamamlamadan herkese açık erişim sağlanmaz ve Google <code>access_denied</code> hatası döndürür.
                    </div>

                    <form method="post" class="form">
                        <input type="hidden" name="action" value="add_business">
                        <div class="form-group">
                            <label for="name">İşletme Adı</label>
                            <input type="text" name="name" id="name" placeholder="Örn: Poyraz Halı Yıkama" value="<?= e($_POST['name'] ?? '') ?>" required <?= $connectionError ? 'disabled' : '' ?>>
                        </div>
                        <div class="form-group">
                            <label for="google_location">Google Konum Kimliği</label>
                            <input type="text" name="google_location" id="google_location" placeholder="accounts/.../locations/..." value="<?= e($_POST['google_location'] ?? '') ?>" required <?= $connectionError ? 'disabled' : '' ?>>
                            <p class="form-hint">Business Profile API'de listelenen tam kaynak adını (ör. <code>accounts/123456789/locations/987654321</code>) girin. Google Haritalar bağlantısı veya Place ID girersen "Bağlantıyı Test Et" işlemi uygun konumu otomatik bulmaya çalışır.</p>
                        </div>
                        <div class="form-group">
                            <label for="google_client_id">Google OAuth Client ID</label>
                            <input type="text" name="google_client_id" id="google_client_id" placeholder="Örn: 1234567890-abc.apps.googleusercontent.com" value="<?= e($_POST['google_client_id'] ?? '') ?>" required <?= $connectionError ? 'disabled' : '' ?>>
                        </div>
                        <div class="form-group">
                            <label for="google_client_secret">Google OAuth Client Secret</label>
                            <input type="password" name="google_client_secret" id="google_client_secret" placeholder="Google Cloud konsolundaki gizli anahtar" required <?= $connectionError ? 'disabled' : '' ?>>
                        </div>
                        <div class="form-group">
                            <label for="google_redirect_uri">Authorized Redirect URI</label>
                            <input type="url" name="google_redirect_uri" id="google_redirect_uri" placeholder="Örn: <?= e($defaultRedirectUri) ?>" value="<?= e($_POST['google_redirect_uri'] ?? $defaultRedirectUri) ?>" required <?= $connectionError ? 'disabled' : '' ?>>
                            <p class="form-hint">Google Cloud Console &rarr; OAuth 2.0 Client ayarlarında aynı URL'yi yetkili yönlendirme listesine ekleyin.</p>
                        </div>
                        <div class="form-group">
                            <label for="google_javascript_origin">Authorized JavaScript Origin</label>
                            <input type="url" name="google_javascript_origin" id="google_javascript_origin" placeholder="Örn: <?= e($defaultJavascriptOrigin) ?>" value="<?= e($_POST['google_javascript_origin'] ?? $defaultJavascriptOrigin) ?>" required <?= $connectionError ? 'disabled' : '' ?>>
                            <p class="form-hint">Google Cloud Console &rarr; OAuth 2.0 Client ayarlarında bu alan adını "Authorized JavaScript origins" listesine ekleyin.</p>
                        </div>
                        <div class="form-group">
                            <label for="authorization_code">Yetkilendirme Kodu (opsiyonel)</label>
                            <input type="text" name="authorization_code" id="authorization_code" placeholder="İlk kurulumda alınan yetkilendirme kodu" <?= $connectionError ? 'disabled' : '' ?>>
                            <p class="form-hint">Kod girmediğin durumda "Bağlantıyı Test Et" bölümünden Google OAuth yetkilendirmesini tamamlayabilirsin.</p>
                        </div>
                        <div class="form-group">
                            <label for="gemini_api_key">Gemini API Anahtarı</label>
                            <input type="text" name="gemini_api_key" id="gemini_api_key" placeholder="AI anahtarını buraya gir" required <?= $connectionError ? 'disabled' : '' ?>>
                        </div>
                        <div class="form-group">
                            <label for="gemini_model">Gemini Modeli (opsiyonel)</label>
                            <input type="text" name="gemini_model" id="gemini_model" placeholder="Örn: gemini-2.5-flash-lite-preview-09-2025" value="<?= e($_POST['gemini_model'] ?? '') ?>" <?= $connectionError ? 'disabled' : '' ?>>
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
                                                <?php $statusMeta = connection_status_meta($business['connectionStatus'] ?? ''); ?>
                                                <dl class="definition-list">
                                                    <div class="definition-list__item">
                                                        <dt>Google Konum</dt>
                                                        <dd><code><?= e($business['googleLocation']) ?></code></dd>
                                                    </div>
                                                    <div class="definition-list__item">
                                                        <dt>Client ID</dt>
                                                        <dd><code><?= e($business['googleClientId']) ?></code></dd>
                                                    </div>
                                                    <div class="definition-list__item">
                                                        <dt>Client Secret</dt>
                                                        <dd><code title="<?= e($business['googleClientSecret']) ?>"><?= e(mask_token($business['googleClientSecret'])) ?></code></dd>
                                                    </div>
                                                    <div class="definition-list__item">
                                                        <dt>Redirect URI</dt>
                                                        <dd><code><?= e($business['googleRedirectUri']) ?></code></dd>
                                                    </div>
                                                    <div class="definition-list__item">
                                                        <dt>JavaScript Origin</dt>
                                                        <dd><code><?= e($business['googleJavascriptOrigin']) ?></code></dd>
                                                    </div>
                                                    <div class="definition-list__item">
                                                        <dt>Access Token</dt>
                                                        <dd><code title="<?= e($business['googleAccessToken']) ?>"><?= e(mask_token($business['googleAccessToken'] ?? '', 6, 4)) ?></code></dd>
                                                    </div>
                                                    <div class="definition-list__item">
                                                        <dt>Refresh Token</dt>
                                                        <dd><code title="<?= e($business['googleRefreshToken']) ?>"><?= e(mask_token($business['googleRefreshToken'] ?? '', 6, 4)) ?></code></dd>
                                                    </div>
                                                    <div class="definition-list__item">
                                                        <dt>Token Sonu</dt>
                                                        <dd><?= format_datetime($business['googleAccessTokenExpiresAt'] ?? null) ?></dd>
                                                    </div>
                                                    <div class="definition-list__item">
                                                        <dt>Bağlantı</dt>
                                                        <dd>
                                                            <span class="<?= e($statusMeta['class']) ?>"><?= e($statusMeta['label']) ?></span>
                                                            <?php if (!empty($business['connectionMessage'])): ?>
                                                                <div class="muted"><?= e($business['connectionMessage']) ?></div>
                                                            <?php endif; ?>
                                                            <div class="muted">Son test: <?= format_datetime($business['connectionCheckedAt'] ?? null) ?></div>
                                                        </dd>
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
                                            <td>
                                                <div class="actions-stack">
                                                    <a class="link" href="?business_id=<?= $business['id'] ?>">Yorumları Gör</a>
                                                    <?php
                                                    $authUrl = null;
                                                    try {
                                                        $oauthHelper = new GoogleOAuthClient($business['googleClientId'], $business['googleClientSecret'], $business['googleRedirectUri'] ?? null);
                                                        $authUrl = $oauthHelper->buildAuthorizationUrl((string)$business['id']);
                                                    } catch (Throwable $exception) {
                                                        $authUrl = null;
                                                    }
                                                    ?>
                                                    <?php if ($authUrl): ?>
                                                        <a class="button button--ghost" href="<?= e($authUrl) ?>" target="_blank" rel="noopener">Yetkilendirme Linki</a>
                                                    <?php endif; ?>
                                                    <?php $authorizationFieldId = 'authorization_code_' . $business['id']; ?>
                                                    <form method="post" class="inline-form inline-form--test" aria-labelledby="<?= $authorizationFieldId ?>_label">
                                                        <input type="hidden" name="action" value="test_connection">
                                                        <input type="hidden" name="business_id" value="<?= $business['id'] ?>">
                                                        <div class="inline-form__header" id="<?= $authorizationFieldId ?>_label">
                                                            <span class="inline-form__title">Google Bağlantı Testi</span>
                                                            <p class="inline-form__hint">Yetkilendirme kodu girersen yeni jeton oluşturulur, boş bırakırsan kayıtlı jetonla test yapılır.</p>
                                                        </div>
                                                        <div class="inline-form__controls">
                                                            <label class="sr-only" for="<?= $authorizationFieldId ?>">Yetkilendirme kodu</label>
                                                            <input type="text" name="authorization_code" id="<?= $authorizationFieldId ?>" class="inline-input" placeholder="Yetkilendirme kodu (opsiyonel)">
                                                            <button type="submit" class="button">Bağlantıyı Test Et</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </td>
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
