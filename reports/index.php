<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require_once __DIR__ . '/../includes/header.php';

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

// عدد فواتير التذاكر في الفترة (للعرض)
$q = $pdo->prepare('SELECT COUNT(*) cnt FROM sale_ticket WHERE date BETWEEN ? AND ?');
$q->execute([$from, $to]);
$ticketCount = (int)$q->fetchColumn();

// الإيرادات مقسّمة: تذاكر + منتجات + إيرادات أخرى
$q = $pdo->prepare("
    SELECT
      COALESCE(SUM(CASE WHEN t.ticket_id IS NOT NULL THEN t.amount ELSE 0 END),0) AS tickets,
      COALESCE(SUM(CASE WHEN t.sale_id   IS NOT NULL THEN t.amount ELSE 0 END),0) AS products,
      COALESCE(SUM(CASE WHEN t.ticket_id IS NULL AND t.sale_id IS NULL THEN t.amount ELSE 0 END),0) AS other
    FROM transaction t JOIN financial_item f ON t.category_id=f.id
    WHERE f.financial_type='incomes' AND t.date BETWEEN ? AND ?");
$q->execute([$from, $to]);
$inc = $q->fetch();

$q = $pdo->prepare("SELECT COALESCE(SUM(t.amount),0) FROM transaction t JOIN financial_item f ON t.category_id=f.id WHERE f.financial_type='expenses' AND t.date BETWEEN ? AND ?");
$q->execute([$from, $to]);
$totalExpense = (float)$q->fetchColumn();

$ticketIncome  = (float)$inc['tickets'];
$productIncome = (float)$inc['products'];
$otherIncome   = (float)$inc['other'];
$totalIncome   = $ticketIncome + $productIncome + $otherIncome;
$net = $totalIncome - $totalExpense;

// تفاصيل الحركات
$q = $pdo->prepare('SELECT t.*, f.name cat_name, f.financial_type FROM transaction t JOIN financial_item f ON t.category_id=f.id WHERE t.date BETWEEN ? AND ? ORDER BY t.date DESC');
$q->execute([$from, $to]);
$transactions = $q->fetchAll();
?>
<div class="toolbar">
    <h1 class="page-title">التقارير</h1>
    <button class="btn" onclick="window.print()">🖨️ طباعة التقرير</button>
</div>

<div class="card no-print-border">
    <form method="get" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-row" style="margin:0;"><label>من تاريخ</label><input type="date" name="from" value="<?= e($from) ?>"></div>
        <div class="form-row" style="margin:0;"><label>إلى تاريخ</label><input type="date" name="to" value="<?= e($to) ?>"></div>
        <button class="btn">عرض</button>
    </form>
</div>

<h3 style="margin:18px 0 12px;">الفترة من <?= e($from) ?> إلى <?= e($to) ?></h3>

<div class="stats">
    <div class="stat"><div class="num"><?= money($ticketIncome) ?></div><div class="label">إيراد التذاكر (<?= $ticketCount ?> فاتورة)</div></div>
    <div class="stat"><div class="num"><?= money($productIncome) ?></div><div class="label">مبيعات المنتجات</div></div>
    <div class="stat"><div class="num"><?= money($otherIncome) ?></div><div class="label">إيرادات أخرى</div></div>
</div>

<div class="stats">
    <div class="stat"><div class="num" style="color:var(--success)"><?= money($totalIncome) ?></div><div class="label">إجمالي الإيرادات</div></div>
    <div class="stat"><div class="num" style="color:var(--danger)"><?= money($totalExpense) ?></div><div class="label">إجمالي المصروفات</div></div>
    <div class="stat"><div class="num" style="color:<?= $net>=0?'var(--success)':'var(--danger)' ?>"><?= money($net) ?></div><div class="label">صافي الربح</div></div>
</div>

<div class="card">
    <h3>تفاصيل الحركات المالية في الفترة</h3>
    <?php if (!$transactions): ?><div class="empty">لا توجد حركات في هذه الفترة</div><?php else: ?>
    <table>
        <thead><tr><th>التاريخ</th><th>البند</th><th>النوع</th><th>المبلغ</th><th>ملاحظات</th></tr></thead>
        <tbody>
        <?php foreach ($transactions as $t): ?>
            <tr>
                <td><?= e($t['date']) ?></td>
                <td><?= e($t['cat_name']) ?></td>
                <td><?= $t['financial_type']==='incomes' ? 'إيراد' : 'مصروف' ?></td>
                <td><?= money($t['amount']) ?></td>
                <td><?= e($t['description']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
