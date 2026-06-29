<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

// تفاصيل الألعاب خلال الفترة
$q = $pdo->prepare('
    SELECT g.name,
           SUM(i.amount)            AS qty,
           SUM(i.amount * g.price)  AS total
    FROM sale_ticket t
    JOIN sale_ticket_item i ON i.sale_ticket_id = t.id
    JOIN game g             ON i.game_id = g.id
    WHERE t.date BETWEEN ? AND ?
    GROUP BY g.id
    ORDER BY total DESC');
$q->execute([$from, $to]);
$rows = $q->fetchAll();

$gamesTotal = 0;   // إجمالي الألعاب (قيمة)
$ticketsQty = 0;   // عدد التذاكر المباعة (عدد اللعبات)
foreach ($rows as $r) {
    $gamesTotal += (float)$r['total'];
    $ticketsQty += (int)$r['qty'];
}

$barTax = $ticketsQty * 0.01;       // ضريبة بر = العدد × 1 قرش
$vatDue = $gamesTotal * 0.20;       // ضريبة مستحقة = الإجمالي × 20%
$taxTotal = $barTax + $vatDue;      // إجمالي الضرائب المستحقة
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة فترة - <?= e($from) ?> / <?= e($to) ?></title>
    <link rel="stylesheet" href="<?= e(base_url('assets/print.css')) ?>?v=<?= ASSET_VER ?>">
</head>
<body>

<!-- اختيار الفترة (لا يُطبع) -->
<div class="period-bar no-print">
    <form method="get" action="<?= e(base_url('tickets/period.php')) ?>">
        <label>من <input type="date" name="from" value="<?= e($from) ?>"></label>
        <label>إلى <input type="date" name="to" value="<?= e($to) ?>"></label>
        <button class="btn" type="submit">عرض</button>
    </form>
</div>

<div class="receipt">
    <img src="<?= e(base_url('assets/logo.png')) ?>?v=<?= ASSET_VER ?>" alt="<?= e(APP_NAME) ?>" class="receipt-logo">
    <h1><?= APP_NAME ?></h1>
    <div class="sub"><?= APP_TAGLINE ?> — فاتورة فترة</div>

    <div class="meta">
        <div><span>من تاريخ:</span><span><?= e($from) ?></span></div>
        <div><span>إلى تاريخ:</span><span><?= e($to) ?></span></div>
    </div>

    <?php if (!$rows): ?>
        <div class="foot" style="margin:20px 0;">لا توجد مبيعات في هذه الفترة</div>
    <?php else: ?>
    <table>
        <thead><tr><th>اللعبة</th><th>العدد</th><th>الإجمالي</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['name']) ?></td>
                <td><?= (int)$r['qty'] ?></td>
                <td><?= number_format((float)$r['total'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="grand"><span>إجمالي الألعاب:</span><span><?= money($gamesTotal) ?></span></div>
        <div><span>عدد التذاكر المباعة:</span><span><?= $ticketsQty ?></span></div>
        <div><span>ضريبة بر (× 1 قرش):</span><span><?= money($barTax) ?></span></div>
        <div><span>ضريبة مستحقة (20%):</span><span><?= money($vatDue) ?></span></div>
        <div class="grand"><span>إجمالي الضرائب المستحقة:</span><span><?= money($taxTotal) ?></span></div>
    </div>
    <?php endif; ?>

    <div class="foot">🎈 <?= APP_NAME ?> 🎈</div>
</div>

<div class="no-print" style="text-align:center;margin-top:20px;">
    <button class="btn" onclick="window.print()">🖨️ طباعة</button>
    <a class="btn" style="background:#64748b" href="<?= e(base_url('tickets/index.php')) ?>">رجوع</a>
</div>
</body>
</html>
