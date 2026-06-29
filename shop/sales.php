<?php
require_once __DIR__ . '/../includes/header.php';

// حذف فاتورة منتجات (يرجّع المخزون ويحذف الإيراد المرتبط تلقائياً)
if (isset($_GET['delete'])) {
    $sid = (int)$_GET['delete'];
    $pdo->beginTransaction();
    try {
        $its = $pdo->prepare('SELECT product_id, quantity FROM sale_item WHERE sale_id=?');
        $its->execute([$sid]);
        $restore = $pdo->prepare('UPDATE product SET stock = stock + ? WHERE id=?');
        foreach ($its->fetchAll() as $it) {
            $restore->execute([(int)$it['quantity'], (int)$it['product_id']]);
        }
        $pdo->prepare('DELETE FROM sale WHERE id=?')->execute([$sid]);
        $pdo->commit();
        flash('تم حذف الفاتورة وإرجاع الكميات للمخزون');
    } catch (Exception $ex) {
        $pdo->rollBack();
        flash('خطأ: ' . $ex->getMessage(), 'danger');
    }
    redirect(base_url('shop/sales.php'));
}

// تفاصيل فاتورة
$viewId = (int)($_GET['view'] ?? 0);
$viewItems = [];
if ($viewId) {
    $stmt = $pdo->prepare('SELECT si.*, p.name, p.price FROM sale_item si JOIN product p ON si.product_id=p.id WHERE si.sale_id=?');
    $stmt->execute([$viewId]);
    $viewItems = $stmt->fetchAll();
}

$sales = $pdo->query('SELECT s.*, c.name AS customer_name FROM sale s LEFT JOIN client c ON s.customer_id=c.id ORDER BY s.id DESC')->fetchAll();
?>
<div class="toolbar">
    <h1 class="page-title">مبيعات المنتجات</h1>
    <a class="btn" href="<?= e(base_url('shop/pos.php')) ?>">🛒 بيع جديد</a>
</div>

<?php if ($viewId && $viewItems): ?>
<div class="card">
    <h3>تفاصيل الفاتورة <?= e(sale_no($viewId)) ?></h3>
    <table>
        <thead><tr><th>المنتج</th><th>السعر</th><th>الكمية</th><th>الإجمالي</th></tr></thead>
        <tbody>
        <?php foreach ($viewItems as $it): ?>
            <tr><td><?= e($it['name']) ?></td><td><?= money($it['price']) ?></td><td><?= $it['quantity'] ?></td><td><?= money($it['price']*$it['quantity']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="card">
    <?php if (!$sales): ?><div class="empty">لا توجد مبيعات</div><?php else: ?>
    <table>
        <thead><tr><th>رقم الفاتورة</th><th>التاريخ</th><th>العميل</th><th>الإجمالي</th><th>بعد الخصم</th><th>إجراءات</th></tr></thead>
        <tbody>
        <?php foreach ($sales as $s): ?>
            <tr>
                <td><strong><?= e(sale_no($s['id'])) ?></strong></td>
                <td><?= e($s['date'] ?: substr($s['created_at'],0,10)) ?></td>
                <td><?= e($s['customer_name'] ?: 'نقدي') ?></td>
                <td><?= money($s['total_price']) ?></td>
                <td><strong><?= money($s['after_discount']) ?></strong></td>
                <td class="actions">
                    <a class="btn btn-sm btn-secondary" href="<?= e(base_url('shop/sales.php?view=' . $s['id'])) ?>">تفاصيل</a>
                    <a class="btn btn-sm" style="background:var(--sky)" target="_blank" href="<?= e(base_url('shop/print.php?id=' . $s['id'])) ?>">🖨️ طباعة</a>
                    <a class="btn btn-sm btn-danger" href="<?= e(base_url('shop/sales.php?delete=' . $s['id'])) ?>" onclick="return confirm('حذف الفاتورة وإرجاع الكميات للمخزون؟')">حذف</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
