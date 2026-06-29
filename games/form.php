<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$game = ['name' => '', 'price' => '', 'icon' => '🎮'];
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM game WHERE id = ?');
    $stmt->execute([$id]);
    $game = $stmt->fetch() ?: $game;
}

$icons = ['🎮','🕹️','🤸','🏎️','🥽','🐸','🏒','🏀','🏍️','🚂','🚒','🚗','🚃','🎠','🎡','🎢','🎯','🎨','🍭','🎲','⚽','🛝','🦄','🚀'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $price = (float)$_POST['price'];
    $icon  = trim($_POST['icon'] ?? '') ?: '🎮';
    if ($id) {
        $pdo->prepare('UPDATE game SET name=?, price=?, icon=? WHERE id=?')->execute([$name, $price, $icon, $id]);
        flash('تم تعديل اللعبة');
    } else {
        $pdo->prepare('INSERT INTO game (name, price, icon) VALUES (?, ?, ?)')->execute([$name, $price, $icon]);
        flash('تمت إضافة اللعبة');
    }
    redirect(base_url('games/index.php'));
}
?>
<h1 class="page-title"><?= $id ? 'تعديل لعبة' : 'إضافة لعبة' ?></h1>
<div class="card">
    <form method="post">
        <div class="form-row">
            <label>اسم اللعبة</label>
            <input type="text" name="name" value="<?= e($game['name']) ?>" required>
        </div>
        <div class="form-row">
            <label>السعر (ج.م)</label>
            <input type="number" step="0.01" name="price" value="<?= e($game['price']) ?>" required>
        </div>
        <div class="form-row">
            <label>الرمز (إيموجي)</label>
            <input type="text" name="icon" id="iconInput" value="<?= e($game['icon'] ?? '🎮') ?>" maxlength="8" style="max-width:120px;font-size:22px;text-align:center;">
            <div class="icon-picker">
                <?php foreach ($icons as $ic): ?>
                    <button type="button" class="icon-opt" onclick="document.getElementById('iconInput').value=this.textContent"><?= $ic ?></button>
                <?php endforeach; ?>
            </div>
            <small style="color:var(--muted)">اختر من القائمة أو اكتب أي إيموجي تحبه</small>
        </div>
        <button class="btn" type="submit">حفظ</button>
        <a class="btn btn-secondary" href="<?= e(base_url('games/index.php')) ?>">إلغاء</a>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
