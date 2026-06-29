<?php
require_once __DIR__ . '/../includes/header.php';

$products = $pdo->query('SELECT * FROM product ORDER BY name')->fetchAll();
$clients  = $pdo->query('SELECT id, name FROM client ORDER BY name')->fetchAll();

// ===== حفظ البيع =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids      = $_POST['product_id'] ?? [];
    $qtys     = $_POST['quantity']   ?? [];
    $discount = (float)($_POST['discount'] ?? 0);
    $customer = (int)($_POST['customer_id'] ?? 0) ?: null;

    $items = [];
    foreach ($ids as $i => $pid) {
        $pid = (int)$pid;
        $q = (int)($qtys[$i] ?? 0);
        if ($pid && $q > 0) $items[$pid] = ($items[$pid] ?? 0) + $q;
    }

    if ($items) {
        $pdo->beginTransaction();
        try {
            $date = date('Y-m-d');
            $pdo->prepare('INSERT INTO sale (customer_id, date, discount) VALUES (?,?,?)')
                ->execute([$customer, $date, $discount]);
            $saleId = (int)$pdo->lastInsertId();

            $total = 0;
            $ins = $pdo->prepare('INSERT INTO sale_item (sale_id,product_id,quantity) VALUES (?,?,?)');
            $upd = $pdo->prepare('UPDATE product SET stock = stock - ? WHERE id=?');
            $get = $pdo->prepare('SELECT name, price, stock FROM product WHERE id=?');
            foreach ($items as $pid => $q) {
                $get->execute([$pid]);
                $p = $get->fetch();
                if (!$p) throw new Exception('منتج غير موجود');
                if ($p['stock'] < $q) throw new Exception('الكمية غير متوفرة في المخزون للمنتج: ' . $p['name']);
                $ins->execute([$saleId, $pid, $q]);
                $upd->execute([$q, $pid]);
                $total += $p['price'] * $q;
            }
            $after = $total - ($total * $discount / 100);
            $pdo->prepare('UPDATE sale SET total_price=?, after_discount=? WHERE id=?')
                ->execute([$total, $after, $saleId]);

            sync_sale_income($pdo, $saleId, $after, $date);

            $pdo->commit();
            flash('تم حفظ فاتورة المنتجات ' . sale_no($saleId));
            redirect(base_url('shop/print.php?id=' . $saleId));
        } catch (Exception $ex) {
            $pdo->rollBack();
            flash($ex->getMessage(), 'danger');
            redirect(base_url('shop/pos.php'));
        }
    } else {
        flash('اختر منتجاً واحداً على الأقل', 'danger');
        redirect(base_url('shop/pos.php'));
    }
}
?>
<div class="toolbar">
    <h1 class="page-title">🛒 بيع منتجات</h1>
    <a class="btn btn-secondary" href="<?= e(base_url('shop/sales.php')) ?>">📋 المبيعات</a>
</div>

<?php if (!$products): ?>
    <div class="alert alert-danger">لا توجد منتجات. أضف منتجات أولاً من صفحة المنتجات والمخزن.</div>
<?php else: ?>
<div class="pos">
    <div class="pos-products">
        <?php foreach ($products as $p): $out = ($p['stock'] <= 0); ?>
            <button type="button" class="prod <?= $out ? 'prod-out' : '' ?>"
                    data-id="<?= $p['id'] ?>"
                    data-name="<?= e($p['name']) ?>"
                    data-price="<?= $p['price'] ?>"
                    data-stock="<?= (int)$p['stock'] ?>"
                    <?= $out ? 'disabled' : '' ?>
                    onclick="addItem(this)">
                <span class="prod-name"><?= e($p['name']) ?></span>
                <span class="prod-price"><?= money($p['price']) ?></span>
                <span class="prod-stock"><?= $out ? 'نفد المخزون' : ('متاح: ' . (int)$p['stock']) ?></span>
            </button>
        <?php endforeach; ?>
    </div>

    <aside class="pos-cart">
        <div class="cart-head">الفاتورة</div>
        <div style="padding:10px 14px 0;">
            <label style="font-size:13px;">العميل (اختياري)</label>
            <select id="customer" style="width:100%;">
                <option value="">عميل نقدي</option>
                <?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div id="cartItems" class="cart-items">
            <div class="cart-empty">لسه مفيش منتجات — دوس على منتج لإضافته</div>
        </div>
        <div class="cart-foot">
            <div class="cart-row"><span>الإجمالي</span><span id="subTotal">0.00 ج.م</span></div>
            <div class="cart-row cart-discount">
                <label for="discount">خصم %</label>
                <input type="number" id="discount" value="0" min="0" max="100" step="0.01" oninput="renderCart()">
            </div>
            <div class="cart-row cart-grand"><span>المطلوب</span><span id="grandTotal">0.00 ج.م</span></div>
            <form method="post" id="payForm" onsubmit="return prepareSubmit()">
                <input type="hidden" name="discount" id="discountField" value="0">
                <input type="hidden" name="customer_id" id="customerField" value="">
                <div id="hiddenItems"></div>
                <button type="submit" class="btn btn-success btn-pay">💳 دفع وطباعة</button>
                <button type="button" class="btn btn-danger btn-clear" onclick="clearCart()">مسح</button>
            </form>
        </div>
    </aside>
</div>

<script>
const cart = {};

function addItem(btn) {
    const id = btn.dataset.id;
    const stock = parseInt(btn.dataset.stock);
    if (!cart[id]) cart[id] = { id, name: btn.dataset.name, price: parseFloat(btn.dataset.price), qty: 0, stock };
    if (cart[id].qty >= stock) { alert('وصلت لأقصى كمية متاحة في المخزون'); return; }
    cart[id].qty++;
    renderCart();
}
function changeQty(id, delta) {
    if (!cart[id]) return;
    if (delta > 0 && cart[id].qty >= cart[id].stock) { alert('وصلت لأقصى كمية متاحة في المخزون'); return; }
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
        box.innerHTML = '<div class="cart-empty">لسه مفيش منتجات — دوس على منتج لإضافته</div>';
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
    document.getElementById('subTotal').textContent = money(sub);
    document.getElementById('grandTotal').textContent = money(sub - (sub * disc / 100));
}

function prepareSubmit() {
    const keys = Object.keys(cart);
    if (keys.length === 0) { alert('اختر منتجاً واحداً على الأقل'); return false; }
    const box = document.getElementById('hiddenItems');
    box.innerHTML = '';
    keys.forEach(id => {
        box.insertAdjacentHTML('beforeend',
            `<input type="hidden" name="product_id[]" value="${cart[id].id}">
             <input type="hidden" name="quantity[]" value="${cart[id].qty}">`);
    });
    document.getElementById('discountField').value = document.getElementById('discount').value || 0;
    document.getElementById('customerField').value = document.getElementById('customer').value || '';
    return true;
}
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
