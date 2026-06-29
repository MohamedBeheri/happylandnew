<?php
// ===== إعدادات الاتصال بقاعدة البيانات =====
// عدّل هذه القيم حسب إعدادات WAMP عندك (الافتراضي يعمل مباشرة مع WAMP)
define('DB_HOST', 'localhost');
define('DB_NAME', 'kidsarea');
define('DB_USER', 'root');
define('DB_PASS', '');       // WAMP الافتراضي بدون كلمة مرور

define('APP_NAME', 'هابي لاند');
define('APP_NAME_EN', 'Happy Land');
define('APP_TAGLINE', 'شبين الكوم – نادي الجمهورية');
// رقم نسخة الأصول (CSS/الصور) — زوّده لو غيّرت اللوجو أو التصميم لإجبار المتصفح على التحديث
define('ASSET_VER', '7');

// المنطقة الزمنية
date_default_timezone_set('Africa/Cairo');

// الاتصال عبر PDO
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('فشل الاتصال بقاعدة البيانات: ' . $e->getMessage()
        . '<br>تأكد من تشغيل WAMP واستيراد ملف database.sql');
}
