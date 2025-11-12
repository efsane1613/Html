<?php
session_start();

$code = isset($_GET['code']) ? (string)$_GET['code'] : '';
$state = isset($_GET['state']) ? (string)$_GET['state'] : '';
$error = isset($_GET['error']) ? (string)$_GET['error'] : '';
$errorDescription = isset($_GET['error_description']) ? (string)$_GET['error_description'] : '';

$redirectTarget = '../index.php';
if ($state !== '') {
    $redirectTarget .= '?business_id=' . rawurlencode($state);
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

            <?php if ($error !== ''): ?>
                <div class="alert alert-error">
                    <strong>Hata:</strong> <?= e($error) ?>
                    <?php if ($errorDescription !== ''): ?>
                        <div class="muted"><?= e($errorDescription) ?></div>
                    <?php endif; ?>
                </div>
                <p class="login-hint">Google Cloud Console ayarlarını kontrol edip yeniden dene.</p>
            <?php elseif ($code === ''): ?>
                <div class="alert alert-warning">Google OAuth yanıtında yetkilendirme kodu bulunamadı.</div>
                <p class="login-hint">OAuth istemcini kontrol edip bağlantıyı tekrar dene.</p>
            <?php else: ?>
                <div class="alert alert-success">Google yetkilendirme kodu başarıyla alındı.</div>
                <div class="form-group">
                    <label>Yetkilendirme Kodu</label>
                    <textarea readonly rows="4" class="inline-input" onclick="this.select();" aria-label="Yetkilendirme kodu"><?= e($code) ?></textarea>
                    <p class="form-hint">Kodu kopyalayıp yönetim panelindeki "Bağlantıyı Test Et" alanına yapıştırarak access/refresh token oluşturabilirsiniz.</p>
                </div>
                <?php if ($state !== ''): ?>
                    <p class="login-hint">Bu yetkilendirme isteği <strong>#<?= e($state) ?></strong> numaralı işletme için başlatıldı.</p>
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
