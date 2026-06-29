<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require_once __DIR__ . '/../includes/header.php';

if (isset($_GET['delete'])) {
    // حركات التذاكر والمبيعات تُحذف من صفحاتها فقط
    $pdo->prepare('DELETE FROM transaction WHERE id = ? AND ticket_id IS NULL AND sale_id IS NULL')->execute([(int)$_GET['delete']]);
    flash('تم حذف الحركة');
    redirect(base_url('financials/index.php'));
}
if (isset($_GET['delitem'])) {
    $pdo->prepare('DELETE FROM financial_item WHERE id = ?')->execute([(int)$_GET['delitem']]);
    flash('تم حذف البند');
    redirect(base_url('financials/index.php'));
}

// إضافة بند
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_submit'])) {
    $pdo->prepare('INSERT INTO financial_item (name, financial_type) VALUES (?, ?)')
        ->execute([trim($_POST['item_name']), $_POST['financial_type']]);
    flash('تمت إضافة البند');
    redirect(base_url('financials/index.php'));
}
// إضافة حركة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trans_submit'])) {
    $pdo->prepare('INSERT INTO transaction (date, amount, category_id, description) VALUES (?,?,?,?)')
        ->execute([$_POST['date'], (float)$_POST['amount'], (int)$_POST['category_id'], trim($_POST['description']) ?: null]);
    flash('تمت إضافة الحركة');
    redirect(base_url('financials/index.php'));
}

$items = $pdo->query('SELECT * FROM financial_item ORDER BY financial_type, name')->fetchAll();
$trans = $pdo->query('SELECT t.*, f.name AS cat_name, f.financial_type FROM transaction t JOIN financial_item f ON t.category_id=f.id ORDER BY t.date DESC, t.id DESC')->fetchAll();

$incomes  = $pdo->query("SELECT COALESCE(SUM(t.amount),0) FROM transaction t JOIN financial_item f ON t.category_id=f.id WHERE f.financial_type='incomes'")->fetchColumn();
$expenses = $pdo->query("SELECT COALESCE(SUM(t.amount),0) FROM transaction t JOIN financial_item f ON t.category_id=f.id WHERE f.financial_type='expenses'")->fetchColumn();
?>
<h1 class="page-title">الإيرادات والمصروفات</h1>

<div class="stats">
    <div class="stat"><div class="num" style="color:var(--success)"><?= money($incomes) ?></div><div class="label">إجمالي الإيرادات</div></div>
    <div class="stat"><div class="num" style="color:var(--danger)"><?= money($expenses) ?></div><div class="label">إجمالي المصروفات</div></div>
    <div class="stat"><div class="num"><?= money($incomes - $expenses) ?></div><div class="label">الصافي</div></div>
</div>

<div class="card">
    <h3>إضافة حركة مالية</h3>
    <?php if (!$items): ?>
        <div class="alert alert-danger">أضف بنداً مالياً أولاً من الأسفل.</div>
    <?php else: ?>
    <form method="post">
        <div class="form-grid">
            <div class="form-row"><label>البند</label><select name="category_id" required>
                <?php foreach ($items as $it): ?><option value="<?= $it['id'] ?>"><?= e($it['name']) ?> (<?= $it['financial_type']==='incomes'?'إيراد':'مصروف' ?>)</option><?php endforeach; ?>
            </select></div>
            <div class="form-row"><label>المبلغ</label><input type="number" step="0.01" name="amount" required></div>
            <div class="form-row"><label>التاريخ</label><input type="date" name="date" value="<?= date('Y-m-d') ?>" required></div>
            <div class="form-row"><label>ملاحظات</label><input type="text" name="description"></div>
        </div>
        <button class="btn" name="trans_submit" value="1" type="submit">حفظ</button>
    </form>
    <?php endif; ?>
</div>

<div class="card">
    <h3>الحركات المالية</h3>
    <?php if (!$trans): ?><div class="empty">لا توجد حركات</div><?php else: ?>
    <table>
        <thead><tr><th>التاريخ</th><th>البند</th><th>النوع</th><th>المبلغ</th><th>ملاحظات</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($trans as $t): ?>
            <tr>
                <td><?= e($t['date']) ?></td>
                <td><?= e($t['cat_name']) ?></td>
                <td><?= $t['financial_type']==='incomes' ? '<span class="badge badge-green">إيراد</span>' : '<span class="badge badge-red">مصروف</span>' ?></td>
                <td><?= money($t['amount']) ?></td>
                <td><?= e($t['description']) ?></td>
                <td>
                    <?php if ($t['ticket_id']): ?>
                        <span class="badge" style="background:#ede7fb;color:#5f1fc4">🎟️ تذكرة</span>
                    <?php elseif ($t['sale_id']): ?>
                        <span class="badge" style="background:#e0f2fe;color:#075985">🛒 منتجات</span>
                    <?php else: ?>
                        <a class="btn btn-sm btn-danger" href="<?= e(base_url('financials/index.php?delete=' . $t['id'])) ?>" onclick="return confirm('حذف الحركة؟')">حذف</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="card">
    <h3>البنود المالية</h3>
    <form method="post" style="display:flex;gap:8px;max-width:520px;margin-bottom:16px;">
        <input type="text" name="item_name" placeholder="اسم البند" required>
        <select name="financial_type" style="max-width:140px;"><option value="incomes">إيراد</option><option value="expenses">مصروف</option></select>
        <button class="btn" name="item_submit" value="1">إضافة</button>
    </form>
    <?php if ($items): ?>
    <table>
        <thead><tr><th>البند</th><th>النوع</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr><td><?= e($it['name']) ?></td>
                <td><?= $it['financial_type']==='incomes'?'إيراد':'مصروف' ?></td>
                <td><a class="btn btn-sm btn-danger" href="<?= e(base_url('financials/index.php?delitem=' . $it['id'])) ?>" onclick="return confirm('حذف البند وكل حركاته؟')">حذف</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
