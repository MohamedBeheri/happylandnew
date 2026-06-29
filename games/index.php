<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require_once __DIR__ . '/../includes/header.php';

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM game WHERE id = ?');
    $stmt->execute([(int)$_GET['delete']]);
    flash('تم حذف اللعبة');
    redirect(base_url('games/index.php'));
}

$games = $pdo->query('SELECT * FROM game ORDER BY name')->fetchAll();
?>
<div class="toolbar">
    <h1 class="page-title">الألعاب</h1>
    <a class="btn" href="<?= e(base_url('games/form.php')) ?>">➕ إضافة لعبة</a>
</div>

<div class="card">
    <?php if (!$games): ?>
        <div class="empty">لا توجد ألعاب بعد</div>
    <?php else: ?>
    <table>
        <thead><tr><th>#</th><th>الرمز</th><th>اسم اللعبة</th><th>السعر</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($games as $g): ?>
            <tr>
                <td><?= $g['id'] ?></td>
                <td style="font-size:22px"><?= e($g['icon'] ?? '🎮') ?></td>
                <td><?= e($g['name']) ?></td>
                <td><?= money($g['price']) ?></td>
                <td class="actions">
                    <a class="btn btn-sm btn-secondary" href="<?= e(base_url('games/form.php?id=' . $g['id'])) ?>">تعديل</a>
                    <a class="btn btn-sm btn-danger" href="<?= e(base_url('games/index.php?delete=' . $g['id'])) ?>" onclick="return confirm('حذف اللعبة؟')">حذف</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
