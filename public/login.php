<?php
session_start();

if (!empty($_SESSION['authenticated'])) {
    header('Location: /index.php');
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
        header('Location: /index.php');
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
    <title>Yorum Botu Giriş</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-brand">
                <div class="login-icon">🤖</div>
                <div>
                    <h1>Yorum Botu</h1>
                    <p>Yönetim paneline erişmek için giriş yap.</p>
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
                <button type="submit">Giriş Yap</button>
            </form>

            <p class="login-hint">Varsayılan bilgiler: <strong>admin / admin</strong></p>
        </div>
    </div>
</body>
</html>
