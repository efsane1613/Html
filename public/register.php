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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = strtolower(sanitize($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $plan = $_POST['plan'] ?? 'basic';

    if (!$name || !$email || !$password) {
        $message = '<div class="alert error">Lütfen tüm alanları doldurun.</div>';
    } else {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $message = '<div class="alert error">Bu e-posta zaten kayıtlı.</div>';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users(name, email, password, plan, created_at) VALUES(?,?,?,?,NOW())');
            $stmt->execute([$name, $email, $hash, $plan]);
            logEvent('info', 'Yeni kullanıcı kaydı: ' . $email);
            header('Location: /public/login.php?registered=1');
            exit;
        }
    }
}
include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h2>Hesap Oluştur</h2>
    <?= $message; ?>
    <form method="post">
        <label>Ad Soyad</label>
        <input type="text" name="name" required>
        <label>E-posta</label>
        <input type="email" name="email" required>
        <label>Şifre</label>
        <input type="password" name="password" required>
        <label>Paket</label>
        <select name="plan">
            <option value="basic">Basic - 99€/ay</option>
            <option value="pro">Pro - 199€/ay</option>
        </select>
        <button class="button" type="submit">Kayıt Ol</button>
    </form>
    <p>Hesabın var mı? <a href="/public/login.php">Giriş yap</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
