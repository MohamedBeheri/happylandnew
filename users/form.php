<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$u = ['name' => '', 'username' => '', 'role' => 'cashier', 'is_active' => 1, 'is_root' => 0];
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id=?');
    $stmt->execute([$id]);
    $u = $stmt->fetch() ?: $u;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = ($_POST['role'] ?? 'cashier') === 'admin' ? 'admin' : 'cashier';
    $active   = isset($_POST['is_active']) ? 1 : 0;

    if ($username === '') {
        $error = 'اسم الدخول مطلوب';
    } elseif (!$id && $password === '') {
        $error = 'كلمة المرور مطلوبة للمستخدم الجديد';
    } else {
        try {
            if ($id) {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare('UPDATE users SET name=?, username=?, password=?, role=?, is_active=? WHERE id=?')
                        ->execute([$name, $username, $hash, $role, $active, $id]);
                } else {
                    $pdo->prepare('UPDATE users SET name=?, username=?, role=?, is_active=? WHERE id=?')
                        ->execute([$name, $username, $role, $active, $id]);
                }
                flash('تم تعديل المستخدم');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('INSERT INTO users (name, username, password, role, is_active, is_superuser) VALUES (?,?,?,?,?,?)')
                    ->execute([$name, $username, $hash, $role, $active, $role === 'admin' ? 1 : 0]);
                flash('تمت إضافة المستخدم');
            }
            redirect(base_url('users/index.php'));
        } catch (PDOException $ex) {
            $error = 'اسم الدخول مستخدم بالفعل أو حدث خطأ';
        }
    }
}
?>
<h1 class="page-title"><?= $id ? 'تعديل مستخدم' : 'إضافة مستخدم' ?></h1>
<div class="card" style="max-width:560px;">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <div class="form-row">
            <label>الاسم</label>
            <input type="text" name="name" value="<?= e($u['name']) ?>">
        </div>
        <div class="form-row">
            <label>اسم الدخول</label>
            <input type="text" name="username" value="<?= e($u['username']) ?>" required <?= $u['is_root'] ? 'readonly' : '' ?>>
        </div>
        <div class="form-row">
            <label>كلمة المرور <?= $id ? '<small>(اتركها فارغة للإبقاء على القديمة)</small>' : '' ?></label>
            <input type="text" name="password" autocomplete="new-password" <?= $id ? '' : 'required' ?>>
        </div>
        <div class="form-row">
            <label>الصلاحية</label>
            <select name="role" <?= $u['is_root'] ? 'disabled' : '' ?>>
                <option value="cashier" <?= $u['role']==='cashier'?'selected':'' ?>>كاشير (بيع فقط)</option>
                <option value="admin"   <?= $u['role']==='admin'?'selected':'' ?>>أدمن (كل الصلاحيات)</option>
            </select>
            <?php if ($u['is_root']): ?><input type="hidden" name="role" value="admin"><?php endif; ?>
        </div>
        <div class="form-row">
            <label><input type="checkbox" name="is_active" value="1" <?= $u['is_active'] ? 'checked' : '' ?> style="width:auto;"> الحساب نشط</label>
        </div>
        <button class="btn" type="submit">حفظ</button>
        <a class="btn btn-secondary" href="<?= e(base_url('users/index.php')) ?>">إلغاء</a>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
