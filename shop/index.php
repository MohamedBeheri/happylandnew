<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require_once __DIR__ . '/../includes/header.php';

if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM product WHERE id = ?')->execute([(int)$_GET['delete']]);
    flash('تم حذف المنتج');
    redirect(base_url('shop/index.php'));
}
if (isset($_GET['delcat'])) {
    $pdo->prepare('DELETE FROM product_category WHERE id = ?')->execute([(int)$_GET['delcat']]);
    flash('تم حذف التصنيف');
    redirect(base_url('shop/index.php'));
}

// إضافة/تعديل منتج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_submit'])) {
    $d = [trim($_POST['name']), trim($_POST['description']) ?: null, (int)$_POST['category_id'],
          (float)$_POST['price'], (int)$_POST['stock']];
    $pid = (int)($_POST['id'] ?? 0);
    if ($pid) {
        $d[] = $pid;
        $pdo->prepare('UPDATE product SET name=?,description=?,category_id=?,price=?,stock=? WHERE id=?')->execute($d);
        flash('تم تعديل المنتج');
    } else {
        $pdo->prepare('INSERT INTO product (name,description,category_id,price,stock) VALUES (?,?,?,?,?)')->execute($d);
        flash('تمت إضافة المنتج');
    }
    redirect(base_url('shop/index.php'));
}
// إضافة تصنيف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_submit'])) {
    $pdo->prepare('INSERT INTO product_category (name) VALUES (?)')->execute([trim($_POST['cat_name'])]);
    flash('تمت إضافة التصنيف');
    redirect(base_url('shop/index.php'));
}

$editId = (int)($_GET['edit'] ?? 0);
$prod = ['name'=>'','description'=>'','category_id'=>'','price'=>'','stock'=>0];
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM product WHERE id=?');
    $stmt->execute([$editId]);
    $prod = $stmt->fetch() ?: $prod;
}

$categories = $pdo->query('SELECT * FROM product_category ORDER BY name')->fetchAll();
$products = $pdo->query('SELECT p.*, c.name AS cat_name FROM product p JOIN product_category c ON p.category_id=c.id ORDER BY p.name')->fetchAll();
?>
<div class="toolbar">
    <h1 class="page-title">المنتجات والمخزن</h1>
</div>

<div class="card">
    <h3><?= $editId ? 'تعديل منتج' : 'إضافة منتج' ?></h3>
    <?php if (!$categories): ?>
        <div class="alert alert-danger">أضف تصنيفاً أولاً من الأسفل.</div>
    <?php else: ?>
    <form method="post">
        <input type="hidden" name="id" value="<?= $editId ?>">
        <div class="form-grid">
            <div class="form-row"><label>الاسم</label><input type="text" name="name" value="<?= e($prod['name']) ?>" required></div>
            <div class="form-row"><label>التصنيف</label><select name="category_id">
                <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (string)$prod['category_id']===(string)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
            </select></div>
            <div class="form-row"><label>السعر</label><input type="number" step="0.01" name="price" value="<?= e($prod['price']) ?>" required></div>
            <div class="form-row"><label>المخزون</label><input type="number" name="stock" value="<?= e($prod['stock']) ?>"></div>
        </div>
        <div class="form-row"><label>الوصف</label><textarea name="description" rows="2"><?= e($prod['description']) ?></textarea></div>
        <button class="btn" name="product_submit" value="1" type="submit">حفظ</button>
        <?php if ($editId): ?><a class="btn btn-secondary" href="<?= e(base_url('shop/index.php')) ?>">إلغاء</a><?php endif; ?>
    </form>
    <?php endif; ?>
</div>

<div class="card">
    <h3>المنتجات</h3>
    <?php if (!$products): ?><div class="empty">لا توجد منتجات</div><?php else: ?>
    <table>
        <thead><tr><th>الاسم</th><th>التصنيف</th><th>السعر</th><th>المخزون</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= e($p['name']) ?></td>
                <td><?= e($p['cat_name']) ?></td>
                <td><?= money($p['price']) ?></td>
                <td><?= $p['stock'] ?: '<span class="badge badge-red">نفد</span>' ?></td>
                <td class="actions">
                    <a class="btn btn-sm btn-secondary" href="<?= e(base_url('shop/index.php?edit=' . $p['id'])) ?>">تعديل</a>
                    <a class="btn btn-sm btn-danger" href="<?= e(base_url('shop/index.php?delete=' . $p['id'])) ?>" onclick="return confirm('حذف المنتج؟')">حذف</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="card">
    <h3>تصنيفات المنتجات</h3>
    <form method="post" style="display:flex;gap:8px;max-width:420px;margin-bottom:16px;">
        <input type="text" name="cat_name" placeholder="اسم التصنيف" required>
        <button class="btn" name="category_submit" value="1">إضافة</button>
    </form>
    <?php if ($categories): ?>
    <table>
        <thead><tr><th>التصنيف</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($categories as $c): ?>
            <tr><td><?= e($c['name']) ?></td>
                <td><a class="btn btn-sm btn-danger" href="<?= e(base_url('shop/index.php?delcat=' . $c['id'])) ?>" onclick="return confirm('حذف التصنيف وكل منتجاته؟')">حذف</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
