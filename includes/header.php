<?php
require_once __DIR__ . '/functions.php';
require_login();
$u = current_user();
$cur = basename($_SERVER['SCRIPT_NAME']);
$curDir = basename(dirname($_SERVER['SCRIPT_NAME']));
function navItem($file, $dir, $label, $cur, $curDir) {
    if ($dir === '') {
        $active = ($cur === $file && $curDir !== 'tickets' && $curDir !== 'shop') ? 'active' : '';
    } else {
        $active = ($curDir === $dir && $cur === $file) ? 'active' : '';
    }
    $href = base_url(($dir ? $dir . '/' : '') . $file);
    echo "<a class=\"$active\" href=\"" . e($href) . "\">$label</a>";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= e(base_url('assets/style.css')) ?>?v=<?= ASSET_VER ?>">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">
            <img src="<?= e(base_url('assets/logo.png')) ?>?v=<?= ASSET_VER ?>" alt="<?= e(APP_NAME) ?>" class="brand-logo">
            <div class="brand-name"><?= APP_NAME ?></div>
            <div class="brand-sub"><?= APP_TAGLINE ?></div>
        </div>
        <nav>
            <?php if (is_admin()): ?>
                <?php navItem('index.php', '', 'الرئيسية', $cur, $curDir); ?>
            <?php endif; ?>
            <div class="group">العمليات</div>
            <?php navItem('pos.php', 'tickets', 'بيع تذاكر', $cur, $curDir); ?>
            <?php navItem('pos.php', 'shop', 'بيع منتجات', $cur, $curDir); ?>
            <?php navItem('index.php', 'tickets', 'فواتير التذاكر', $cur, $curDir); ?>
            <?php navItem('sales.php', 'shop', 'مبيعات المنتجات', $cur, $curDir); ?>
            <?php if (is_admin()): ?>
            <div class="group">الإدارة</div>
            <?php navItem('index.php', 'games', 'الألعاب', $cur, $curDir); ?>
            <?php navItem('index.php', 'clients', 'العملاء', $cur, $curDir); ?>
            <?php navItem('index.php', 'shop', 'المنتجات والمخزن', $cur, $curDir); ?>
            <?php navItem('index.php', 'users', 'المستخدمين', $cur, $curDir); ?>
            <div class="group">المالية</div>
            <?php navItem('index.php', 'financials', 'الإيرادات والمصروفات', $cur, $curDir); ?>
            <?php navItem('index.php', 'reports', 'التقارير', $cur, $curDir); ?>
            <?php endif; ?>
        </nav>
    </aside>
    <div class="main">
        <div class="topbar">
            <div></div>
            <div class="user">
                مرحباً، <strong><?= e($u['name'] ?: $u['username']) ?></strong>
                <a href="<?= e(base_url('logout.php')) ?>">خروج</a>
            </div>
        </div>
        <div class="content">
        <?php if ($f = flash()): ?>
            <div class="alert alert-<?= $f['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($f['msg']) ?></div>
        <?php endif; ?>
