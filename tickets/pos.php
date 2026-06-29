<?php
require_once __DIR__ . '/../includes/header.php';

$games = $pdo->query('SELECT * FROM game ORDER BY id')->fetchAll();

// ===== وضع التعديل: تحميل تذكرة موجودة =====
$editId = (int)($_GET['edit'] ?? 0);
$editItems = [];
$editTicket = null;
if ($editId) {
    $st = $pdo->prepare('SELECT * FROM sale_ticket WHERE id=?');
    $st->execute([$editId]);
    $editTicket = $st->fetch();
    if ($editTicket) {
        $iq = $pdo->prepare('SELECT game_id, amount FROM sale_ticket_item WHERE sale_ticket_id=?');
        $iq->execute([$editId]);
        foreach ($iq->fetchAll() as $r) {
            $editItems[(int)$r['game_id']] = (int)$r['amount'];
        }
    } else {
        $editId = 0;
    }
}

// ===== حفظ / تحديث الفاتورة =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids      = $_POST['game_id'] ?? [];
    $amts     = $_POST['amount']  ?? [];
    $discount = (float)($_POST['discount'] ?? 0);
    $postEdit = (int)($_POST['edit_id'] ?? 0);

    $items = [];
    foreach ($ids as $i => $gid) {
        $gid = (int)$gid;
        $a = (int)($amts[$i] ?? 0);
        if ($gid && $a > 0) $items[$gid] = ($items[$gid] ?? 0) + $a;
    }

    if ($items) {
        $pdo->beginTransaction();
        try {
            if ($postEdit) {
                // تاريخ التذكرة يفضل زي ما هو
                $d = $pdo->prepare('SELECT date FROM sale_ticket WHERE id=?');
                $d->execute([$postEdit]);
                $tDate = $d->fetchColumn() ?: date('Y-m-d');
                $pdo->prepare('UPDATE sale_ticket SET discount=? WHERE id=?')->execute([$discount, $postEdit]);
                $pdo->prepare('DELETE FROM sale_ticket_item WHERE sale_ticket_id=?')->execute([$postEdit]);
                $stId = $postEdit;
            } else {
                $tDate = date('Y-m-d');
                $pdo->prepare('INSERT INTO sale_ticket (date, discount) VALUES (?, ?)')->execute([$tDate, $discount]);
                $stId = (int)$pdo->lastInsertId();
            }

            $total = 0;
            $ins = $pdo->prepare('INSERT INTO sale_ticket_item (sale_ticket_id,game_id,amount) VALUES (?,?,?)');
            $gp  = $pdo->prepare('SELECT price FROM game WHERE id=?');
            foreach ($items as $gid => $a) {
                $gp->execute([$gid]);
                $price = (float)$gp->fetchColumn();
                $ins->execute([$stId, $gid, $a]);
                $total += $price * $a;
            }
            $after = $total - ($total * $discount / 100);
            $pdo->prepare('UPDATE sale_ticket SET total_price=?, after_discount=? WHERE id=?')
                ->execute([$total, $after, $stId]);

            // تسجيل/تحديث الإيراد تلقائياً
            sync_ticket_income($pdo, $stId, $after, $tDate);

            $pdo->commit();
            flash($postEdit ? ('تم تعديل التذكرة ' . ticket_no($stId)) : ('تم حفظ التذكرة ' . ticket_no($stId)));
            redirect(base_url('tickets/print.php?id=' . $stId));
        } catch (Exception $ex) {
            $pdo->rollBack();
            flash('خطأ: ' . $ex->getMessage(), 'danger');
            redirect(base_url('tickets/pos.php'));
        }
    } else {
        flash('اختر صنفاً واحداً على الأقل', 'danger');
        redirect(base_url('tickets/pos.php' . ($postEdit ? '?edit=' . $postEdit : '')));
    }
}
?>
<div class="toolbar">
    <h1 class="page-title">🎟️ <?= $editId ? ('تعديل التذكرة ' . e(ticket_no($editId))) : 'بيع تذاكر' ?></h1>
    <a class="btn btn-secondary" href="<?= e(base_url('tickets/index.php')) ?>">📋 الفواتير</a>
</div>

<?php if (!$games): ?>
    <div class="alert alert-danger">لا توجد ألعاب. أضف لعبة أولاً من صفحة الألعاب.</div>
<?php else: ?>
<div class="pos">
    <!-- شبكة الألعاب -->
    <div class="pos-products">
        <?php foreach ($games as $g): ?>
            <button type="button" class="prod"
                    data-id="<?= $g['id'] ?>"
                    data-name="<?= e($g['name']) ?>"
                    data-price="<?= $g['price'] ?>"
                    onclick="addItem(this)">
                <span class="prod-icon"><?= e($g['icon'] ?? '🎮') ?></span>
                <span class="prod-name"><?= e($g['name']) ?></span>
                <span class="prod-price"><?= money($g['price']) ?></span>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- لوحة الفاتورة -->
    <aside class="pos-cart">
        <div class="cart-head">الفاتورة</div>
        <div id="cartItems" class="cart-items">
            <div class="cart-empty" id="cartEmpty">لسه مفيش أصناف — دوس على لعبة لإضافتها</div>
        </div>
        <div class="cart-foot">
            <div class="cart-row"><span>الإجمالي</span><span id="subTotal">0.00 ج.م</span></div>
            <div class="cart-row cart-discount">
                <label for="discount">خصم %</label>
                <input type="number" id="discount" value="<?= $editTicket ? rtrim(rtrim($editTicket['discount'],'0'),'.') : '0' ?>"
                       min="0" max="100" step="0.01" oninput="renderCart()">
            </div>
            <div class="cart-row cart-grand"><span>المطلوب</span><span id="grandTotal">0.00 ج.م</span></div>
            <form method="post" id="payForm" onsubmit="return prepareSubmit()">
                <input type="hidden" name="edit_id" value="<?= $editId ?>">
                <input type="hidden" name="discount" id="discountField" value="0">
                <div id="hiddenItems"></div>
                <button type="submit" class="btn btn-success btn-pay">💳 دفع وطباعة</button>
                <button type="button" class="btn btn-danger btn-clear" onclick="clearCart()">مسح</button>
            </form>
        </div>
    </aside>
</div>

<script>
const cart = {};               // {gameId: {id,name,price,qty}}
const editItems = <?= json_encode($editItems, JSON_UNESCAPED_UNICODE) ?>;

function addItem(btn) {
    const id = btn.dataset.id;
    if (!cart[id]) {
        cart[id] = { id, name: btn.dataset.name, price: parseFloat(btn.dataset.price), qty: 0 };
    }
    cart[id].qty++;
    renderCart();
}
function changeQty(id, delta) {
    if (!cart[id]) return;
    cart[id].qty += delta;
    if (cart[id].qty <= 0) delete cart[id];
    renderCart();
}
function removeItem(id) { delete cart[id]; renderCart(); }
function clearCart() { for (const k in cart) delete cart[k]; renderCart(); }

function money(n) { return Number(n).toFixed(2) + ' ج.م'; }

function renderCart() {
    const box = document.getElementById('cartItems');
    const keys = Object.keys(cart);
    let sub = 0;
    if (keys.length === 0) {
        box.innerHTML = '<div class="cart-empty">لسه مفيش أصناف — دوس على لعبة لإضافتها</div>';
    } else {
        let html = '';
        keys.forEach(id => {
            const it = cart[id];
            const line = it.price * it.qty;
            sub += line;
            html += `<div class="cart-item">
                <div class="ci-info"><div class="ci-name">${it.name}</div><div class="ci-price">${money(it.price)}</div></div>
                <div class="ci-qty">
                    <button type="button" onclick="changeQty('${id}',-1)">−</button>
                    <span>${it.qty}</span>
                    <button type="button" onclick="changeQty('${id}',1)">+</button>
                </div>
                <div class="ci-total">${money(line)}</div>
                <button type="button" class="ci-del" onclick="removeItem('${id}')">✕</button>
            </div>`;
        });
        box.innerHTML = html;
    }
    const disc = parseFloat(document.getElementById('discount').value) || 0;
    const grand = sub - (sub * disc / 100);
    document.getElementById('subTotal').textContent = money(sub);
    document.getElementById('grandTotal').textContent = money(grand);
}

function prepareSubmit() {
    const keys = Object.keys(cart);
    if (keys.length === 0) { alert('اختر صنفاً واحداً على الأقل'); return false; }
    const box = document.getElementById('hiddenItems');
    box.innerHTML = '';
    keys.forEach(id => {
        box.insertAdjacentHTML('beforeend',
            `<input type="hidden" name="game_id[]" value="${cart[id].id}">
             <input type="hidden" name="amount[]" value="${cart[id].qty}">`);
    });
    document.getElementById('discountField').value = document.getElementById('discount').value || 0;
    return true;
}

// تحميل أصناف التذكرة في وضع التعديل
window.addEventListener('DOMContentLoaded', () => {
    Object.keys(editItems).forEach(gid => {
        const btn = document.querySelector('.prod[data-id="' + gid + '"]');
        if (btn) {
            cart[gid] = { id: gid, name: btn.dataset.name, price: parseFloat(btn.dataset.price), qty: editItems[gid] };
        }
    });
    renderCart();
});
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
