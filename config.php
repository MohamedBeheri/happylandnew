<?php
// ===== إعدادات الاتصال بقاعدة البيانات =====
// يعمل تلقائياً على Railway (متغيرات البيئة) ويعمل محلياً مع WAMP (القيم الافتراضية)

$DB_HOST = 'localhost';
$DB_PORT = '3306';
$DB_NAME = 'kidsarea';
$DB_USER = 'root';
$DB_PASS = '';   // WAMP الافتراضي بدون كلمة مرور

// (1) لو Railway وفّر رابط اتصال كامل (MYSQL_URL / DATABASE_URL) نفكّكه
$dbUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');
if ($dbUrl) {
    $p = parse_url($dbUrl);
    if (!empty($p['host'])) $DB_HOST = $p['host'];
    if (!empty($p['port'])) $DB_PORT = (string)$p['port'];
    if (isset($p['user']))  $DB_USER = $p['user'];
    if (isset($p['pass']))  $DB_PASS = $p['pass'];
    if (!empty($p['path'])) $DB_NAME = ltrim($p['path'], '/');
}

// (2) أو متغيرات منفصلة (MYSQLHOST ... التي يوفّرها Railway)
if (getenv('MYSQLHOST'))     $DB_HOST = getenv('MYSQLHOST');
if (getenv('MYSQLPORT'))     $DB_PORT = getenv('MYSQLPORT');
if (getenv('MYSQLDATABASE')) $DB_NAME = getenv('MYSQLDATABASE');
if (getenv('MYSQLUSER'))     $DB_USER = getenv('MYSQLUSER');
if (getenv('MYSQLPASSWORD') !== false && getenv('MYSQLPASSWORD') !== '') $DB_PASS = getenv('MYSQLPASSWORD');

define('DB_HOST', $DB_HOST);
define('DB_NAME', $DB_NAME);
define('DB_USER', $DB_USER);
define('DB_PASS', $DB_PASS);

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
        'mysql:host=' . $DB_HOST . ';port=' . $DB_PORT . ';dbname=' . $DB_NAME . ';charset=utf8mb4',
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('فشل الاتصال بقاعدة البيانات: ' . $e->getMessage());
}

// ===== إنشاء الجداول والبيانات تلقائياً أول مرة (قاعدة بيانات فاضية) =====
try {
    $pdo->query('SELECT 1 FROM users LIMIT 1');
} catch (PDOException $e) {
    $sqlFile = __DIR__ . '/database.sql';
    if (is_readable($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        // إزالة أوامر CREATE DATABASE و USE (Railway يوفّر قاعدة بياناته الخاصة)
        $sql = preg_replace('/CREATE\s+DATABASE\b.*?;/is', '', $sql);
        $sql = preg_replace('/USE\s+[^;]+;/i', '', $sql);
        $pdo->exec($sql);
    }
}
