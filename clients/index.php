<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require_once __DIR__ . '/../includes/header.php';

if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM client WHERE id = ?')->execute([(int)$_GET['delete']]);
    flash('تم حذف العميل');
    redirect(base_url('clients/index.php'));
}

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare('SELECT * FROM client WHERE name LIKE ? OR phone LIKE ? OR national_id LIKE ? ORDER BY name');
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
    $clients = $stmt->fetchAll();
} else {
    $clients = $pdo->query('SELECT * FROM client ORDER BY name')->fetchAll();
}
?>
<div class="toolbar">
    <h1 class="page-title">العملاء</h1>
    <a class="btn" href="<?= e(base_url('clients/add.php')) ?>">➕ إضافة عميل</a>
</div>

<div class="card">
    <form method="get" style="margin-bottom:16px;display:flex;gap:8px;max-width:420px;">
        <input type="text" name="q" placeholder="بحث بالاسم أو الهاتف أو الرقم القومي" value="<?= e($search) ?>">
        <button class="btn">بحث</button>
    </form>
    <?php if (!$clients): ?>
        <div class="empty">لا يوجد عملاء</div>
    <?php else: ?>
    <table>
        <thead><tr><th>#</th><th>الاسم</th><th>الهاتف</th><th>الرقم القومي</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($clients as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= e($c['name']) ?></td>
                <td><?= e($c['phone']) ?></td>
                <td><?= e($c['national_id']) ?></td>
                <td>
                    <?php if ($c['is_blocked']): ?>
                        <span class="badge badge-red">محظور</span>
                    <?php else: ?>
                        <span class="badge badge-green">نشط</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <a class="btn btn-sm btn-secondary" href="<?= e(base_url('clients/add.php?id=' . $c['id'])) ?>">تعديل</a>
                    <a class="btn btn-sm btn-danger" href="<?= e(base_url('clients/index.php?delete=' . $c['id'])) ?>" onclick="return confirm('حذف العميل؟')">حذف</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
