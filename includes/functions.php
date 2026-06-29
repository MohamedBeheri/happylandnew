<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== دوال مساعدة =====

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect(base_url('login.php'));
    }
}

function current_user() {
    return [
        'id'       => $_SESSION['user_id']   ?? null,
        'name'     => $_SESSION['user_name'] ?? '',
        'username' => $_SESSION['username']  ?? '',
        'role'     => $_SESSION['role']      ?? 'admin',
    ];
}

function is_admin() {
    return ($_SESSION['role'] ?? '') === 'admin';
}

// تتطلب صلاحية أدمن، وإلا تحوّل الكاشير لشاشة البيع
function require_admin() {
    require_login();
    if (!is_admin()) {
        flash('غير مسموح لك بالدخول لهذه الصفحة', 'danger');
        redirect(base_url('tickets/pos.php'));
    }
}

// تحديد المسار الأساسي للمشروع تلقائياً (يعمل في أي مجلد فرعي)
function base_url($path = '') {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // إذا كنا داخل مجلد فرعي (مثل games/) نرجع خطوة للوراء
    $segments = ['games','tickets','clients','shop','financials','reports','users'];
    foreach ($segments as $seg) {
        if (preg_match('#/' . $seg . '$#', $dir)) {
            $dir = preg_replace('#/' . $seg . '$#', '', $dir);
            break;
        }
    }
    $dir = rtrim($dir, '/');
    return $dir . '/' . ltrim($path, '/');
}

function flash($msg = null, $type = 'success') {
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return;
    }
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function money($n) {
    return number_format((float)$n, 2) . ' ج.م';
}

function verify_password($input, $stored) {
    // يدعم bcrypt (password_hash) و SHA-256 (للمستخدم الافتراضي)
    if (password_verify($input, $stored)) {
        return true;
    }
    return hash('sha256', $input) === $stored;
}

// رقم التذكرة بصيغة موحّدة (مع أصفار على الشمال)
function ticket_no($id) {
    return 'HL' . str_pad((string)(int)$id, 6, '0', STR_PAD_LEFT);
}

// رقم فاتورة منتجات
function sale_no($id) {
    return 'SH' . str_pad((string)(int)$id, 6, '0', STR_PAD_LEFT);
}

// بند الماليات الخاص بإيراد التذاكر (يُنشأ تلقائياً لو مش موجود)
function ticket_income_category_id(PDO $pdo) {
    $id = $pdo->query("SELECT id FROM financial_item WHERE name='مبيعات تذاكر' AND financial_type='incomes' LIMIT 1")->fetchColumn();
    if (!$id) {
        $pdo->prepare("INSERT INTO financial_item (name, financial_type) VALUES ('مبيعات تذاكر','incomes')")->execute();
        $id = $pdo->lastInsertId();
    }
    return (int)$id;
}

// مزامنة حركة الإيراد المرتبطة بتذكرة (إنشاء/تحديث)
function sync_ticket_income(PDO $pdo, $ticketId, $amount, $date) {
    $catId = ticket_income_category_id($pdo);
    $exists = $pdo->prepare('SELECT id FROM transaction WHERE ticket_id = ?');
    $exists->execute([$ticketId]);
    $tid = $exists->fetchColumn();
    if ($tid) {
        $pdo->prepare('UPDATE transaction SET amount=?, date=?, category_id=? WHERE id=?')
            ->execute([$amount, $date, $catId, $tid]);
    } else {
        $pdo->prepare('INSERT INTO transaction (date, amount, category_id, description, ticket_id) VALUES (?,?,?,?,?)')
            ->execute([$date, $amount, $catId, 'تذكرة رقم ' . ticket_no($ticketId), $ticketId]);
    }
}

// بند إيراد مبيعات المنتجات
function product_income_category_id(PDO $pdo) {
    $id = $pdo->query("SELECT id FROM financial_item WHERE name='مبيعات منتجات' AND financial_type='incomes' LIMIT 1")->fetchColumn();
    if (!$id) {
        $pdo->prepare("INSERT INTO financial_item (name, financial_type) VALUES ('مبيعات منتجات','incomes')")->execute();
        $id = $pdo->lastInsertId();
    }
    return (int)$id;
}

// مزامنة حركة الإيراد المرتبطة ببيع منتجات (إنشاء/تحديث)
function sync_sale_income(PDO $pdo, $saleId, $amount, $date) {
    $catId = product_income_category_id($pdo);
    $exists = $pdo->prepare('SELECT id FROM transaction WHERE sale_id = ?');
    $exists->execute([$saleId]);
    $tid = $exists->fetchColumn();
    if ($tid) {
        $pdo->prepare('UPDATE transaction SET amount=?, date=?, category_id=? WHERE id=?')
            ->execute([$amount, $date, $catId, $tid]);
    } else {
        $pdo->prepare('INSERT INTO transaction (date, amount, category_id, description, sale_id) VALUES (?,?,?,?,?)')
            ->execute([$date, $amount, $catId, 'فاتورة منتجات ' . sale_no($saleId), $saleId]);
    }
}

// توليد باركود Code39 كـ SVG (أوفلاين بالكامل بدون أي مكتبات)
function barcode_svg($data, $barWidth = 2, $height = 60) {
    $data = strtoupper(preg_replace('/[^0-9A-Z]/', '', (string)$data));
    $map = [
        '0'=>'nnnwwnwnn','1'=>'wnnwnnnnw','2'=>'nnwwnnnnw','3'=>'wnwwnnnnn',
        '4'=>'nnnwwnnnw','5'=>'wnnwwnnnn','6'=>'nnwwwnnnn','7'=>'nnnwnnwnw',
        '8'=>'wnnwnnwnn','9'=>'nnwwnnwnn',
        'A'=>'wnnnnwnnw','B'=>'nnwnnwnnw','C'=>'wnwnnwnnn','D'=>'nnnnwwnnw',
        'E'=>'wnnnwwnnn','F'=>'nnwnwwnnn','G'=>'nnnnnwwnw','H'=>'wnnnnwwnn',
        'I'=>'nnwnnwwnn','J'=>'nnnnwwwnn','K'=>'wnnnnnnww','L'=>'nnwnnnnww',
        'M'=>'wnwnnnnwn','N'=>'nnnnwnnww','O'=>'wnnnwnnwn','P'=>'nnwnwnnwn',
        'Q'=>'nnnnnnwww','R'=>'wnnnnnwwn','S'=>'nnwnnnwwn','T'=>'nnnnwnwwn',
        'U'=>'wwnnnnnnw','V'=>'nwwnnnnnw','W'=>'wwwnnnnnn','X'=>'nwnnwnnnw',
        'Y'=>'wwnnwnnnn','Z'=>'nwwnwnnnn','*'=>'nwnnwnwnn',
    ];
    $code = '*' . $data . '*';
    $narrow = $barWidth; $wide = $barWidth * 3;
    $x = 0; $rects = '';
    for ($i = 0; $i < strlen($code); $i++) {
        $ch = $code[$i];
        if (!isset($map[$ch])) continue;
        $pat = $map[$ch];
        for ($j = 0; $j < 9; $j++) {
            $w = ($pat[$j] === 'w') ? $wide : $narrow;
            if ($j % 2 === 0) { // العناصر الزوجية أعمدة سوداء
                $rects .= '<rect x="' . $x . '" y="0" width="' . $w . '" height="' . $height . '"/>';
            }
            $x += $w;
        }
        $x += $narrow; // فاصل بين الحروف
    }
    $tw = $x;
    return '<svg xmlns="http://www.w3.org/2000/svg" width="100%" viewBox="0 0 ' . $tw . ' ' . $height . '" '
         . 'preserveAspectRatio="xMidYMid meet" shape-rendering="crispEdges">'
         . '<rect width="' . $tw . '" height="' . $height . '" fill="#fff"/>'
         . '<g fill="#000">' . $rects . '</g></svg>';
}
