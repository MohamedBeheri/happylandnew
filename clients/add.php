<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$c = ['name'=>'','national_id'=>'','gender'=>'male','birth_date'=>'','age'=>'',
      'phone'=>'','phone2'=>'','email'=>'','address'=>'','is_blocked'=>0];
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM client WHERE id = ?');
    $stmt->execute([$id]);
    $c = $stmt->fetch() ?: $c;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        trim($_POST['name']),
        trim($_POST['national_id']) ?: null,
        $_POST['gender'],
        $_POST['birth_date'] ?: null,
        $_POST['age'] !== '' ? (int)$_POST['age'] : null,
        trim($_POST['phone']),
        trim($_POST['phone2']) ?: null,
        trim($_POST['email']) ?: null,
        trim($_POST['address']) ?: null,
        isset($_POST['is_blocked']) ? 1 : 0,
    ];
    try {
        if ($id) {
            $sql = 'UPDATE client SET name=?,national_id=?,gender=?,birth_date=?,age=?,phone=?,phone2=?,email=?,address=?,is_blocked=? WHERE id=?';
            $data[] = $id;
            $pdo->prepare($sql)->execute($data);
            flash('تم تعديل بيانات العميل');
        } else {
            $data[] = current_user()['id'];
            $sql = 'INSERT INTO client (name,national_id,gender,birth_date,age,phone,phone2,email,address,is_blocked,added_by_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
            $pdo->prepare($sql)->execute($data);
            flash('تمت إضافة العميل');
        }
        redirect(base_url('clients/index.php'));
    } catch (PDOException $ex) {
        $error = 'خطأ: قد يكون الرقم القومي مكرّر';
    }
}
?>
<h1 class="page-title"><?= $id ? 'تعديل عميل' : 'إضافة عميل' ?></h1>
<div class="card">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <div class="form-grid">
            <div class="form-row"><label>الاسم</label><input type="text" name="name" value="<?= e($c['name']) ?>" required></div>
            <div class="form-row"><label>الرقم القومي</label><input type="text" name="national_id" value="<?= e($c['national_id']) ?>"></div>
            <div class="form-row"><label>النوع</label>
                <select name="gender">
                    <option value="male" <?= $c['gender']==='male'?'selected':'' ?>>ذكر</option>
                    <option value="female" <?= $c['gender']==='female'?'selected':'' ?>>أنثى</option>
                </select>
            </div>
            <div class="form-row"><label>تاريخ الميلاد</label><input type="date" name="birth_date" value="<?= e($c['birth_date']) ?>"></div>
            <div class="form-row"><label>العمر</label><input type="number" name="age" value="<?= e($c['age']) ?>"></div>
            <div class="form-row"><label>الهاتف</label><input type="text" name="phone" value="<?= e($c['phone']) ?>"></div>
            <div class="form-row"><label>هاتف 2</label><input type="text" name="phone2" value="<?= e($c['phone2']) ?>"></div>
            <div class="form-row"><label>البريد الإلكتروني</label><input type="email" name="email" value="<?= e($c['email']) ?>"></div>
        </div>
        <div class="form-row"><label>العنوان</label><textarea name="address" rows="2"><?= e($c['address']) ?></textarea></div>
        <div class="form-row">
            <label><input type="checkbox" name="is_blocked" style="width:auto" <?= $c['is_blocked']?'checked':'' ?>> محظور</label>
        </div>
        <button class="btn" type="submit">حفظ</button>
        <a class="btn btn-secondary" href="<?= e(base_url('clients/index.php')) ?>">إلغاء</a>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
