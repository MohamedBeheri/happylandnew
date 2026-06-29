<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) redirect(base_url('index.php'));

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && verify_password($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['role']      = $user['role'] ?? 'admin';
        // الكاشير شاشته الأساسية هي نقطة البيع
        redirect(base_url(is_admin() ? 'index.php' : 'tickets/pos.php'));
    } else {
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= e(base_url('assets/style.css')) ?>?v=<?= ASSET_VER ?>">
</head>
<body>
<div class="login-wrap">
    <form class="login-box" method="post">
        <img src="<?= e(base_url('assets/logo.png')) ?>?v=<?= ASSET_VER ?>" alt="<?= e(APP_NAME) ?>" class="login-logo">
        <h2><?= APP_NAME ?> <span class="login-en"><?= APP_NAME_EN ?></span></h2>
        <p><?= APP_TAGLINE ?></p>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <div class="form-row">
            <label>اسم المستخدم</label>
            <input type="text" name="username" autofocus required>
        </div>
        <div class="form-row">
            <label>كلمة المرور</label>
            <input type="password" name="password" required>
        </div>
        <button class="btn" style="width:100%;" type="submit">دخول</button>
        <p style="margin-top:16px;font-size:13px;">الافتراضي: admin / admin123</p>
    </form>
</div>
</body>
</html>
