<?php
session_start();

require __DIR__ . '/../../src/autoload.php';

use App\Config\BusinessRepository;
use App\Database\Connection;
use App\Google\GoogleOAuthClient;

$code = isset($_GET['code']) ? (string)$_GET['code'] : '';
$state = isset($_GET['state']) ? (string)$_GET['state'] : '';
$error = isset($_GET['error']) ? (string)$_GET['error'] : '';
$errorDescription = isset($_GET['error_description']) ? (string)$_GET['error_description'] : '';

$businessId = null;
if ($state !== '' && ctype_digit($state)) {
    $businessId = (int)$state;
}

$redirectTarget = '../index.php';
if ($businessId !== null) {
    $redirectTarget .= '?business_id=' . rawurlencode((string)$businessId);
}

$businessRepository = null;
$databaseError = null;
$business = null;
$tokenStored = false;
$tokenError = null;
$tokenMessage = null;
$oauthErrorHint = null;

try {
    $databaseConfig = require __DIR__ . '/../../config/database.php';
    $pdo = Connection::make($databaseConfig);
    $businessRepository = new BusinessRepository($pdo);
} catch (\Throwable $exception) {
    $businessRepository = null;
    $databaseError = $exception->getMessage();
}

if (isset($businessRepository) && $businessId !== null) {
    $business = $businessRepository->find($businessId);
    if (!$business) {
        $tokenError = 'Yetkilendirme isteğiyle eşleşen işletme kaydı bulunamadı.';
        $_SESSION['flash_error'] = $tokenError;
    }
}

if ($error !== '' && isset($businessRepository, $business) && $businessId !== null) {
    $message = 'Google OAuth hatası: ' . $error;
    if ($errorDescription !== '') {
        $message .= ' - ' . $errorDescription;
    }

    if ($error === 'access_denied') {
        $oauthErrorHint = 'Google bu isteği reddetti. OAuth izin ekranında hesabınızı test kullanıcısı olarak ekleyin veya uygulamayı yayınlayarak alan adınızı doğrulayın.';

        if ($errorDescription !== '' && (stripos($errorDescription, 'verify') !== false || stripos($errorDescription, 'verified') !== false)) {
            $oauthErrorHint .= ' Google Cloud Console &rarr; OAuth consent screen sayfasında "Test users" bölümüne giriş yaptığınız hesabı ekleyip alan doğrulamasını tamamladığınızdan emin olun.';
        }

        $message .= ' (Google hesabınız yetkilendirmeyi reddetti. Test kullanıcısı listesine eklendiğinizden ve uygulamanın doğrulama aşamasını geçtiğinden emin olun.)';
    }

    $businessRepository->updateConnectionStatus($businessId, 'error', $message);
    $_SESSION['flash_error'] = $message;
}

if ($code !== '' && $business && isset($businessRepository) && $businessId !== null) {
    try {
        $oauthClient = new GoogleOAuthClient(
            $business['googleClientId'],
            $business['googleClientSecret'],
            $business['googleRedirectUri'] ?? null
        );

        $tokenResponse = $oauthClient->exchangeAuthorizationCode($code);

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

        $tokenStored = true;
        $tokenMessage = 'Google OAuth yetkilendirmesi tamamlandı ve erişim jetonları kaydedildi.';
        $businessRepository->updateConnectionStatus(
            $businessId,
            'connected',
            'Google OAuth yetkilendirmesi başarıyla tamamlandı.'
        );

        $_SESSION['flash_success'] = $tokenMessage;
    } catch (\Throwable $exception) {
        $tokenError = $exception->getMessage();
        $businessRepository->updateConnectionStatus($businessId, 'error', $tokenError);
        $_SESSION['flash_error'] = 'Google OAuth hatası: ' . $tokenError;
    }
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google OAuth Yönlendirme</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-brand">
                <div class="login-icon">🔑</div>
                <div>
                    <h1>Google OAuth Tamamlandı</h1>
                    <p>Yetkilendirme sonucu aşağıda yer alıyor.</p>
                </div>
            </div>

            <?php if ($databaseError !== null): ?>
                <div class="alert alert-error">
                    <strong>Veritabanı hatası:</strong> <?= e($databaseError) ?>
                </div>
                <p class="login-hint">Google yetkilendirme kodu alınmış olsa bile veritabanına kaydedilemedi.</p>
            <?php elseif ($error !== ''): ?>
                <div class="alert alert-error">
                    <strong>Hata:</strong> <?= e($error) ?>
                    <?php if ($errorDescription !== ''): ?>
                        <div class="muted"><?= e($errorDescription) ?></div>
                    <?php endif; ?>
                </div>
                <p class="login-hint">Google Cloud Console ayarlarını kontrol edip yeniden dene.</p>
                <?php if ($oauthErrorHint !== null): ?>
                    <div class="alert alert-info"><?= e($oauthErrorHint) ?></div>
                <?php endif; ?>
            <?php elseif ($code === ''): ?>
                <div class="alert alert-warning">Google OAuth yanıtında yetkilendirme kodu bulunamadı.</div>
                <p class="login-hint">OAuth istemcini kontrol edip bağlantıyı tekrar dene.</p>
            <?php else: ?>
                <?php if ($tokenStored): ?>
                    <div class="alert alert-success">Google yetkilendirmesi tamamlandı.</div>
                    <p class="login-hint"><?= e($tokenMessage) ?></p>
                <?php else: ?>
                    <div class="alert alert-warning">Google yetkilendirme kodu alındı ancak otomatik olarak kaydedilemedi.</div>
                    <?php if ($tokenError !== null): ?>
                        <p class="login-hint">Hata: <?= e($tokenError) ?></p>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!$tokenStored): ?>
                    <div class="form-group">
                        <label>Yetkilendirme Kodu</label>
                        <textarea readonly rows="4" class="inline-input" onclick="this.select();" aria-label="Yetkilendirme kodu"><?= e($code) ?></textarea>
                        <p class="form-hint">Kodu yönetim panelindeki "Bağlantıyı Test Et" alanına yapıştırarak jeton kaydını tamamlayabilirsiniz.</p>
                    </div>
                <?php endif; ?>

                <?php if ($businessId !== null): ?>
                    <p class="login-hint">Bu yetkilendirme isteği <strong>#<?= e((string)$businessId) ?></strong> numaralı işletme için başlatıldı.</p>
                <?php endif; ?>
            <?php endif; ?>

            <div class="login-actions">
                <a class="button" href="<?= e($redirectTarget) ?>">Panele Dön</a>
                <a class="button button--ghost" href="../login.php">Giriş Sayfasına Git</a>
            </div>
        </div>
    </div>
</body>
</html>
