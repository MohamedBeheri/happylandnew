<?php
require_once __DIR__ . '/includes/functions.php';
require_admin();
require_once __DIR__ . '/includes/header.php';

$clients   = $pdo->query('SELECT COUNT(*) FROM client')->fetchColumn();
$games     = $pdo->query('SELECT COUNT(*) FROM game')->fetchColumn();
$products  = $pdo->query('SELECT COUNT(*) FROM product')->fetchColumn();

$today = date('Y-m-d');
$todayTickets = $pdo->prepare('SELECT COALESCE(SUM(after_discount),0) FROM sale_ticket WHERE date = ?');
$todayTickets->execute([$today]);
$ticketRevenue = $todayTickets->fetchColumn();

$incomes  = $pdo->query("SELECT COALESCE(SUM(t.amount),0) FROM transaction t JOIN financial_item f ON t.category_id=f.id WHERE f.financial_type='incomes'")->fetchColumn();
$expenses = $pdo->query("SELECT COALESCE(SUM(t.amount),0) FROM transaction t JOIN financial_item f ON t.category_id=f.id WHERE f.financial_type='expenses'")->fetchColumn();
?>
<h1 class="page-title">لوحة التحكم</h1>

<div class="stats">
    <div class="stat"><div class="num"><?= $clients ?></div><div class="label">العملاء</div></div>
    <div class="stat"><div class="num"><?= $games ?></div><div class="label">الألعاب</div></div>
    <div class="stat"><div class="num"><?= $products ?></div><div class="label">المنتجات</div></div>
    <div class="stat"><div class="num"><?= money($ticketRevenue) ?></div><div class="label">إيراد تذاكر اليوم</div></div>
    <div class="stat"><div class="num" style="color:var(--success)"><?= money($incomes) ?></div><div class="label">إجمالي الإيرادات</div></div>
    <div class="stat"><div class="num" style="color:var(--danger)"><?= money($expenses) ?></div><div class="label">إجمالي المصروفات</div></div>
</div>

<div class="card">
    <h3>روابط سريعة</h3>
    <div class="actions" style="flex-wrap:wrap;gap:10px;">
        <a class="btn" href="<?= e(base_url('tickets/pos.php')) ?>">🎟️ بيع تذاكر</a>
        <a class="btn btn-secondary" href="<?= e(base_url('clients/add.php')) ?>">➕ عميل جديد</a>
        <a class="btn btn-secondary" href="<?= e(base_url('games/form.php')) ?>">🎮 لعبة جديدة</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
