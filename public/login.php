<?php
session_start();

if (!empty($_SESSION['authenticated'])) {
    header('Location: index.php');
    exit;
}

$error = null;
$loggedOut = isset($_GET['logged_out']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['authenticated'] = true;
        $_SESSION['flash_success'] = 'Hoş geldin! Yönetim paneline giriş yaptın.';
        header('Location: index.php');
        exit;
    }

    $error = 'Kullanıcı adı veya şifre hatalı.';
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
    <title>Yorum Botu Giriş</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="login-body">
    <div class="login-aurora login-aurora--primary"></div>
    <div class="login-aurora login-aurora--secondary"></div>
    <div class="login-grid">
        <section class="login-showcase">
            <span class="login-showcase__badge">Profesyonel panel</span>
            <h1>Yorum Botu Kontrol Merkezi</h1>
            <p>Google yorumlarını tek ekrandan izleyip Gemini destekli yanıtları saniyeler içinde yayımla. Tüm süreçler senin kontrolünde.</p>
            <ul class="login-showcase__highlights">
                <li>7/24 otomatik yorum taraması</li>
                <li>Çok dilli yapay zeka yanıtları</li>
                <li>Şık raporlama ve işletme yönetimi</li>
            </ul>
        </section>

        <section class="login-card">
            <div class="login-brand">
                <div class="login-icon">🤖</div>
                <div>
                    <h1>Giriş Yap</h1>
                    <p>Yetkili erişim için yönetici hesabını kullan.</p>
                </div>
            </div>

            <?php if ($loggedOut): ?>
                <div class="alert alert-info">Güvenli çıkış yaptın. Tekrar giriş yapabilirsin.</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" class="login-form" autocomplete="off">
                <div class="form-group">
                    <label for="username">Kullanıcı Adı</label>
                    <input type="text" name="username" id="username" placeholder="admin" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" name="password" id="password" placeholder="admin" required>
                </div>
                <button type="submit">Panele Giriş Yap</button>
            </form>

            <p class="login-hint">Varsayılan bilgiler: <strong>admin / admin</strong></p>
        </section>
    </div>
</body>
</html>
