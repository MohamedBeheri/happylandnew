<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require_once __DIR__ . '/../includes/header.php';

if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    $me  = current_user();
    if ($uid === (int)$me['id']) {
        flash('لا يمكنك حذف حسابك الحالي', 'danger');
    } else {
        $isRoot = $pdo->prepare('SELECT is_root FROM users WHERE id=?');
        $isRoot->execute([$uid]);
        if ($isRoot->fetchColumn()) {
            flash('لا يمكن حذف المدير الرئيسي', 'danger');
        } else {
            $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$uid]);
            flash('تم حذف المستخدم');
        }
    }
    redirect(base_url('users/index.php'));
}

$users = $pdo->query('SELECT * FROM users ORDER BY id')->fetchAll();
$roles = ['admin' => 'أدمن', 'cashier' => 'كاشير'];
?>
<div class="toolbar">
    <h1 class="page-title">المستخدمين</h1>
    <a class="btn" href="<?= e(base_url('users/form.php')) ?>">➕ إضافة مستخدم</a>
</div>
<div class="card">
    <table>
        <thead><tr><th>#</th><th>الاسم</th><th>اسم الدخول</th><th>الصلاحية</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= e($u['name']) ?></td>
                <td><?= e($u['username']) ?></td>
                <td>
                    <?php if ($u['role'] === 'admin'): ?><span class="badge" style="background:#ede7fb;color:#5f1fc4">أدمن</span>
                    <?php else: ?><span class="badge badge-green">كاشير</span><?php endif; ?>
                </td>
                <td><?= $u['is_active'] ? '<span class="badge badge-green">نشط</span>' : '<span class="badge badge-red">موقوف</span>' ?></td>
                <td class="actions">
                    <a class="btn btn-sm btn-secondary" href="<?= e(base_url('users/form.php?id=' . $u['id'])) ?>">تعديل</a>
                    <?php if (!$u['is_root']): ?>
                        <a class="btn btn-sm btn-danger" href="<?= e(base_url('users/index.php?delete=' . $u['id'])) ?>" onclick="return confirm('حذف المستخدم؟')">حذف</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
