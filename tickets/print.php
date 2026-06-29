<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM sale_ticket WHERE id = ?');
$stmt->execute([$id]);
$ticket = $stmt->fetch();
if (!$ticket) { die('الفاتورة غير موجودة'); }

$its = $pdo->prepare('SELECT i.amount, g.name, g.price FROM sale_ticket_item i JOIN game g ON i.game_id=g.id WHERE i.sale_ticket_id=?');
$its->execute([$id]);
$items = $its->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة تذاكر <?= e(ticket_no($id)) ?></title>
    <link rel="stylesheet" href="<?= e(base_url('assets/print.css')) ?>?v=<?= ASSET_VER ?>">
</head>
<body onload="window.print()">
<div class="receipt">
    <img src="<?= e(base_url('assets/logo.png')) ?>?v=<?= ASSET_VER ?>" alt="<?= e(APP_NAME) ?>" class="receipt-logo">
    <h1><?= APP_NAME ?></h1>
    <div class="sub"><?= APP_TAGLINE ?> — فاتورة تذاكر</div>
    <div class="meta">
        <div><span>رقم التذكرة:</span><span><?= e(ticket_no($id)) ?></span></div>
        <div><span>التاريخ:</span><span><?= e($ticket['date']) ?></span></div>
    </div>
    <table>
        <thead><tr><th>اللعبة</th><th>السعر</th><th>العدد</th><th>الإجمالي</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?= e($it['name']) ?></td>
                <td><?= number_format($it['price'],2) ?></td>
                <td><?= $it['amount'] ?></td>
                <td><?= number_format($it['price']*$it['amount'],2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="totals">
        <div><span>الإجمالي:</span><span><?= money($ticket['total_price']) ?></span></div>
        <div><span>الخصم:</span><span><?= rtrim(rtrim($ticket['discount'],'0'),'.') ?>%</span></div>
        <div class="grand"><span>المطلوب:</span><span><?= money($ticket['after_discount']) ?></span></div>
    </div>
    <div class="barcode"><?= barcode_svg(ticket_no($id)) ?></div>
    <div class="barcode-no"><?= e(ticket_no($id)) ?></div>
    <div class="foot">شكراً لزيارتكم 🎈</div>
</div>
<div class="no-print">
    <button class="btn" onclick="window.print()">🖨️ طباعة</button>
    <a class="btn" style="background:#64748b" href="<?= e(base_url('tickets/index.php')) ?>">رجوع</a>
</div>
</body>
</html>
