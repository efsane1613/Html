<?php
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../backend/helpers/auth.php';
require_once __DIR__ . '/../backend/helpers/utils.php';

if (isAuthenticated()) {
    header('Location: /public/dashboard.php');
    exit;
}

$message = '';
if (isset($_GET['registered'])) {
    $message = '<div class="alert success">Kayıt tamamlandı, şimdi giriş yapabilirsiniz.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(sanitize($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['email'] === 'admin@bot.com' ? 'admin' : 'user';
        logEvent('info', 'Giriş yapıldı: ' . $email);
        header('Location: /public/dashboard.php');
        exit;
    }
    $message = '<div class="alert error">Geçersiz bilgiler.</div>';
}
include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h2>Giriş Yap</h2>
    <?= $message; ?>
    <form method="post">
        <label>E-posta</label>
        <input type="email" name="email" required>
        <label>Şifre</label>
        <input type="password" name="password" required>
        <button class="button" type="submit">Giriş</button>
    </form>
    <p>Hesabın yok mu? <a href="/public/register.php">Kayıt ol</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
