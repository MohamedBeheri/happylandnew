<?php
require_once __DIR__ . '/../includes/header.php';

// حذف (يحذف الأصناف وحركة الإيراد تلقائياً عبر المفاتيح الأجنبية)
if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM sale_ticket WHERE id = ?')->execute([(int)$_GET['delete']]);
    flash('تم حذف الفاتورة');
    redirect(base_url('tickets/index.php'));
}

// نقل الفاتورة ليوم تاني
if (isset($_GET['move']) && !empty($_GET['date'])) {
    $mid = (int)$_GET['move'];
    $newDate = $_GET['date'];
    $dt = DateTime::createFromFormat('Y-m-d', $newDate);
    if ($dt && $dt->format('Y-m-d') === $newDate) {
        $pdo->prepare('UPDATE sale_ticket SET date=? WHERE id=?')->execute([$newDate, $mid]);
        $pdo->prepare('UPDATE transaction SET date=? WHERE ticket_id=?')->execute([$newDate, $mid]);
        flash('تم نقل التذكرة ' . ticket_no($mid) . ' إلى ' . e($newDate));
    } else {
        flash('تاريخ غير صحيح', 'danger');
    }
    redirect(base_url('tickets/index.php'));
}

// عرض تفاصيل فاتورة
$viewId = (int)($_GET['view'] ?? 0);
$viewItems = [];
if ($viewId) {
    $stmt = $pdo->prepare('SELECT i.*, g.name AS game_name, g.price FROM sale_ticket_item i JOIN game g ON i.game_id=g.id WHERE i.sale_ticket_id=?');
    $stmt->execute([$viewId]);
    $viewItems = $stmt->fetchAll();
}

$tickets = $pdo->query('SELECT * FROM sale_ticket ORDER BY id DESC')->fetchAll();
?>
<div class="toolbar">
    <h1 class="page-title">فواتير التذاكر</h1>
    <div class="actions">
        <a class="btn" style="background:var(--primary)" target="_blank" href="<?= e(base_url('tickets/period.php')) ?>">🧾 فاتورة فترة</a>
        <a class="btn" href="<?= e(base_url('tickets/pos.php')) ?>">🎟️ فاتورة جديدة</a>
    </div>
</div>

<?php if ($viewId && $viewItems): ?>
<div class="card">
    <h3>تفاصيل التذكرة <?= e(ticket_no($viewId)) ?></h3>
    <table>
        <thead><tr><th>اللعبة</th><th>السعر</th><th>العدد</th><th>الإجمالي</th></tr></thead>
        <tbody>
        <?php foreach ($viewItems as $it): ?>
            <tr><td><?= e($it['game_name']) ?></td><td><?= money($it['price']) ?></td><td><?= $it['amount'] ?></td><td><?= money($it['price']*$it['amount']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="card">
    <?php if (!$tickets): ?><div class="empty">لا توجد فواتير</div><?php else: ?>
    <table>
        <thead><tr><th>رقم التذكرة</th><th>التاريخ</th><th>الإجمالي</th><th>الخصم %</th><th>بعد الخصم</th><th>إجراءات</th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
            <tr>
                <td><strong><?= e(ticket_no($t['id'])) ?></strong></td>
                <td><?= e($t['date']) ?></td>
                <td><?= money($t['total_price']) ?></td>
                <td><?= rtrim(rtrim($t['discount'],'0'),'.') ?>%</td>
                <td><strong><?= money($t['after_discount']) ?></strong></td>
                <td class="actions">
                    <a class="btn btn-sm btn-secondary" href="<?= e(base_url('tickets/index.php?view=' . $t['id'])) ?>">تفاصيل</a>
                    <a class="btn btn-sm" style="background:var(--sky)" target="_blank" href="<?= e(base_url('tickets/print.php?id=' . $t['id'])) ?>">🖨️ طباعة</a>
                    <a class="btn btn-sm" style="background:var(--accent)" href="<?= e(base_url('tickets/pos.php?edit=' . $t['id'])) ?>">✏️ تعديل</a>
                    <form method="get" class="move-form" action="<?= e(base_url('tickets/index.php')) ?>">
                        <input type="hidden" name="move" value="<?= $t['id'] ?>">
                        <input type="date" name="date" value="<?= e($t['date']) ?>" required>
                        <button class="btn btn-sm" style="background:var(--primary)" type="submit">نقل</button>
                    </form>
                    <a class="btn btn-sm btn-danger" href="<?= e(base_url('tickets/index.php?delete=' . $t['id'])) ?>" onclick="return confirm('حذف الفاتورة؟')">حذف</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
