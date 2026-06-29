<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT s.*, c.name AS customer_name FROM sale s LEFT JOIN client c ON s.customer_id=c.id WHERE s.id = ?');
$stmt->execute([$id]);
$sale = $stmt->fetch();
if (!$sale) { die('الفاتورة غير موجودة'); }

$its = $pdo->prepare('SELECT si.quantity, p.name, p.price FROM sale_item si JOIN product p ON si.product_id=p.id WHERE si.sale_id=?');
$its->execute([$id]);
$items = $its->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة منتجات <?= e(sale_no($id)) ?></title>
    <link rel="stylesheet" href="<?= e(base_url('assets/print.css')) ?>?v=<?= ASSET_VER ?>">
</head>
<body onload="window.print()">
<div class="receipt">
    <img src="<?= e(base_url('assets/logo.png')) ?>?v=<?= ASSET_VER ?>" alt="<?= e(APP_NAME) ?>" class="receipt-logo">
    <h1><?= APP_NAME ?></h1>
    <div class="sub"><?= APP_TAGLINE ?> — فاتورة منتجات</div>
    <div class="meta">
        <div><span>رقم الفاتورة:</span><span><?= e(sale_no($id)) ?></span></div>
        <div><span>التاريخ:</span><span><?= e($sale['date'] ?: substr($sale['created_at'],0,10)) ?></span></div>
        <?php if ($sale['customer_name']): ?><div><span>العميل:</span><span><?= e($sale['customer_name']) ?></span></div><?php endif; ?>
    </div>
    <table>
        <thead><tr><th>المنتج</th><th>السعر</th><th>الكمية</th><th>الإجمالي</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?= e($it['name']) ?></td>
                <td><?= number_format($it['price'],2) ?></td>
                <td><?= $it['quantity'] ?></td>
                <td><?= number_format($it['price']*$it['quantity'],2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="totals">
        <div><span>الإجمالي:</span><span><?= money($sale['total_price']) ?></span></div>
        <div><span>الخصم:</span><span><?= rtrim(rtrim($sale['discount'],'0'),'.') ?>%</span></div>
        <div class="grand"><span>المطلوب:</span><span><?= money($sale['after_discount']) ?></span></div>
    </div>
    <div class="barcode"><?= barcode_svg(sale_no($id)) ?></div>
    <div class="barcode-no"><?= e(sale_no($id)) ?></div>
    <div class="foot">شكراً لزيارتكم 🎈</div>
</div>
<div class="no-print">
    <button class="btn" onclick="window.print()">🖨️ طباعة</button>
    <a class="btn" style="background:#64748b" href="<?= e(base_url('shop/sales.php')) ?>">رجوع</a>
</div>
</body>
</html>
